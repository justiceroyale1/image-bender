<?php

namespace App\Jobs;

use App\Contracts\ImageGenerator;
use App\Enums\Status;
use App\Models\ImageGeneration;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\RequestException;

class SubmitImageGeneration implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $uniqueFor = 3600; // Lock expires after 1 hour as a safety net

    public function __construct(
        public ImageGeneration $generation,
    ) {}

    public function uniqueId(): string
    {
        return (string) $this->generation->id;
    }

    public function handle(ImageGenerator $generator): void
    {
        try {
            // Reload fresh state from DB; abort if already submitted or terminal
            $this->generation->refresh();

            if ($this->generation->provider_id !== null || $this->generation->status?->isTerminal()) {
                return;
            }

            $data = $generator->generate($this->generation);

            $this->generation->update([
                'provider_id' => $data->id,
                'status' => $data->status,
            ]);
        } catch (RequestException $e) {
            $status = $e->response?->status();
            $error = $e->response?->json('error') ?? [];
            $code = $error['code'] ?? null;

            // Retryable: temporary rate limits, concurrency limits, or 503 overload
            if (in_array($code, ['rate_limit_exceeded', 'concurrency_limit_exceeded', 'overloaded'], true) || $status === 503) {
                $retryAfter = max(2, (int) $e->response?->header('Retry-After'));

                // Add exponential jitter on top of Retry-After
                $this->release($retryAfter * $this->attempts());

                return;
            }

            // Non-retryable (400 content_policy_violation, 422 validation_failed, 429 daily_limit_exceeded, 401)
            $this->generation->update([
                'status' => Status::Failed,
                'provider_error' => $error ?: ['message' => $e->getMessage()],
            ]);

            $this->fail($e);
        }
    }
}
