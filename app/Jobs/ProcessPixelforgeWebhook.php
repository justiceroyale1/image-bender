<?php

namespace App\Jobs;

use App\Enums\Status;
use App\Models\ImageGeneration;
use App\Models\WebhookEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ProcessPixelforgeWebhook implements ShouldQueue
{
    use Queueable;

    /**
     * Retry up to 5 times in case a webhook arrives before provider_id is persisted.
     */
    public int $tries = 5;

    public function __construct(
        public readonly WebhookEvent $webhookEvent,
    ) {}

    public function handle(): void
    {
        if ($this->webhookEvent->processed_at !== null) {
            return;
        }

        /** @var array{id?: string, type?: string, data?: array{object?: array<string, mixed>}} $event */
        $event = $this->webhookEvent->payload ?? [];
        $object = $event['data']['object'] ?? [];
        $providerId = $object['id'] ?? null;

        if (! is_string($providerId) || $providerId === '') {
            $this->markProcessed();

            return;
        }

        $generation = ImageGeneration::where('provider_id', $providerId)->first();

        // Pixelforge can deliver a webhook before POST /v1/generations returns and saves provider_id.
        // Release the job back to the queue briefly so the creation request can finish committing.
        if (! $generation) {
            if ($this->attempts() < $this->tries) {
                $this->release(2);

                return;
            }

            return;
        }

        // Guard against out-of-order webhooks: never move back from a terminal state.
        if ($generation->status->isTerminal()) {
            $this->markProcessed();

            return;
        }

        match ($this->webhookEvent->type) {
            'generation.processing' => $this->handleProcessing($generation, $object),
            'generation.succeeded' => $this->handleSucceeded($generation, $object),
            'generation.failed' => $this->handleFailed($generation, $object),
            default => $this->markProcessed(),
        };
    }

    /**
     * @param  array<string, mixed>  $object
     */
    protected function handleProcessing(ImageGeneration $generation, array $object): void
    {
        DB::transaction(function () use ($generation, $object): void {
            $generation->update([
                'status' => Status::Processing,
                'provider_response' => $object,
            ]);

            $this->markProcessed();
        });
    }

    /**
     * @param  array<string, mixed>  $object
     */
    protected function handleSucceeded(ImageGeneration $generation, array $object): void
    {
        $remoteUrl = is_string($object['image_url'] ?? null) ? $object['image_url'] : null;
        $storedUrl = null;
        $disk = $this->resolveAvailableDisk();

        // Download the PNG outside the DB transaction if a persistent disk is available.
        if (! empty($disk) && ! empty($remoteUrl)) {
            try {
                // Remove ->throw() so 404/500 responses don't throw an exception
                $response = Http::timeout(30)->get($remoteUrl);

                if ($response->successful()) {
                    $path = "generations/{$generation->ulid}.png";

                    if (Storage::disk($disk)->put($path, $response->body())) {
                        $storedUrl = url(Storage::disk($disk)->url($path));
                    }
                }
            } catch (ConnectionException) {
                // Still catch network/timeout errors so the job gracefully falls back to $remoteUrl
            }
        }

        DB::transaction(function () use ($generation, $object, $remoteUrl, $storedUrl): void {
            $providerCost = $this->calculateProviderCost($object);
            $userCost = $this->calculateUserCost($providerCost);

            $generation->update([
                'status' => Status::Succeeded,
                'image_url' => $storedUrl ?? $remoteUrl,
                'image_url_expires_at' => $storedUrl !== null
                    ? null
                    : ($object['image_url_expires_at'] ?? null),
                'provider_cost' => $providerCost,
                'user_cost' => $userCost,
                'provider_response' => $object,
            ]);

            $this->markProcessed();
        });
    }

    /**
     * Resolve the configured storage disk only if it is properly set up and safe to persist to.
     */
    protected function resolveAvailableDisk(): ?string
    {
        $disk = config('ai-image.storage_disk');

        if (! is_string($disk) || $disk === '') {
            return null;
        }

        /** @var array<string, mixed>|null $diskConfig */
        $diskConfig = config("filesystems.disks.{$disk}");

        if (! is_array($diskConfig)) {
            return null;
        }

        // Prevent writing to local/public disk in production (ephemeral filesystem)
        if (app()->isProduction() && ($diskConfig['driver'] ?? null) === 'local') {
            return null;
        }

        // Verify S3-compatible disks actually have credentials and a bucket configured
        if (($diskConfig['driver'] ?? null) === 's3') {
            $hasBucket = is_string($diskConfig['bucket'] ?? null) && $diskConfig['bucket'] !== '';
            $hasKey = is_string($diskConfig['key'] ?? null) && $diskConfig['key'] !== '';
            $hasSecret = is_string($diskConfig['secret'] ?? null) && $diskConfig['secret'] !== '';

            if (! $hasBucket || ! $hasKey || ! $hasSecret) {
                return null;
            }
        }

        return $disk;
    }

    /**
     * @param  array<string, mixed>  $object
     */
    protected function handleFailed(ImageGeneration $generation, array $object): void
    {
        DB::transaction(function () use ($generation, $object): void {
            $generation->update([
                'status' => Status::Failed,
                'provider_error' => $object['error'] ?? null,
                'provider_response' => $object,
            ]);

            $this->markProcessed();
        });
    }

    protected function markProcessed(): void
    {
        $this->webhookEvent->update([
            'processed_at' => now(),
        ]);
    }

    /**
     * Calculate exact USD string (9 decimal places) using bcmath to avoid float drift.
     *
     * @param  array<string, mixed>  $object
     */
    protected function calculateProviderCost(array $object): string
    {
        /** @var array<string, int|string> $usage */
        $usage = is_array($object['usage'] ?? null) ? $object['usage'] : [];
        /** @var array<string, string> $rates */
        $rates = is_array($object['rates'] ?? null) ? $object['rates'] : [];

        $types = ['input_text_tokens', 'input_image_tokens', 'output_tokens', 'reasoning_tokens'];
        $totalPerMillion = '0';

        foreach ($types as $type) {
            $tokens = (string) ((int) ($usage[$type] ?? 0));
            $rate = is_numeric($rates[$type] ?? null) ? (string) $rates[$type] : '0';

            // Multiply tokens * rate at the provided unit (USD per 1M tokens)
            $lineTotal = bcmul($tokens, $rate, 9);
            $totalPerMillion = bcadd($totalPerMillion, $lineTotal, 9);
        }

        // Divide by 1,000,000 with exact 9-decimal-place scale
        return bcdiv($totalPerMillion, '1000000', 9);
    }

    protected function calculateUserCost(string $providerCost): string
    {
        // Add 25% markup (provider_cost * 1.25)
        return bcmul($providerCost, '1.25', 9);
    }
}
