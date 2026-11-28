<?php

namespace App\Services\ImageGeneration;

use App\Contracts\ImageGenerator;
use App\Data\ImageGeneration\GenerationData;
use App\Data\ImageGeneration\UsageData;
use App\Models\ImageGeneration;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PixelforgeImageGenerator implements ImageGenerator
{
    public function __construct(
        protected string $baseUrl,
        protected string $apiKey,
    ) {}

    public function generate(ImageGeneration $generation): GenerationData
    {
        $payload = array_filter([
            'prompt' => $generation->prompt,
            'size' => $generation->size->value,
            'quality' => $generation->quality->value,
            'reference_images' => ! empty($generation->reference_images) ? $generation->reference_images : null,
            'metadata' => [
                'generation_id' => (string) $generation->id,
            ],
        ], fn (string|array|null $value): bool => $value !== null);

        $response = $this->client()
            ->withHeaders([
                'Idempotency-Key' => $generation->idempotency_key,
            ])
            ->post('/generations', $payload)
            ->throw();

        return GenerationData::fromArray($response->json());
    }

    public function usage(?string $startDate = null, ?string $endDate = null): UsageData
    {
        $query = array_filter([
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $cacheKey = 'pixelforge:usage:'.hash('xxh128', $this->apiKey.'|'.json_encode($query));

        $payload = Cache::remember($cacheKey, now()->addMinutes(5), fn (): array => $this->client()
            ->get('/usage', $query)
            ->throw()
            ->json());

        return UsageData::fromArray($payload);
    }

    public function find(string $generationId): GenerationData
    {
        $response = $this->client()
            ->get("/generations/{$generationId}")
            ->throw();

        return GenerationData::fromArray($response->json());
    }

    protected function client(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withToken($this->apiKey)
            ->acceptJson()
            ->asJson()
            ->timeout(15);
    }
}
