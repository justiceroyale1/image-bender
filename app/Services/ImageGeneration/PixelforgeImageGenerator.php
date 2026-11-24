<?php

namespace App\Services\ImageGeneration;

use App\Contracts\ImageGenerator;
use App\Data\GenerationData;
use App\Models\ImageGeneration;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
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
            'size' => $generation->size?->value,
            'quality' => $generation->quality?->value,
            'reference_images' => ! empty($generation->reference_images) ? $generation->reference_images : null,
            'metadata' => [
                'generation_id' => (string) $generation->id,
            ],
        ], fn ($value) => $value !== null);

        $response = $this->client()
            ->withHeaders([
                'Idempotency-Key' => $generation->idempotency_key,
            ])
            ->post('/generations', $payload)
            ->throw();

        return GenerationData::fromArray($response->json());
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
