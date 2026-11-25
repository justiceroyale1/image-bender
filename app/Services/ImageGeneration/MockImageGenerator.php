<?php

namespace App\Services\ImageGeneration;

use App\Contracts\ImageGenerator;
use App\Data\GenerationData;
use App\Enums\Quality;
use App\Enums\Size;
use App\Enums\Status;
use App\Models\ImageGeneration;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

class MockImageGenerator implements ImageGenerator
{
    /** @var array<string, GenerationData> */
    protected array $store = [];

    protected const DEFAULT_RATES = [
        'input_text_tokens' => '5.00',
        'input_image_tokens' => '10.00',
        'output_tokens' => '40.00',
        'reasoning_tokens' => '12.50',
    ];

    public function generate(ImageGeneration $generation): GenerationData
    {
        $id = 'gen_mock_'.Str::ulid();
        $size = ($generation->size ?? Size::Square)->value;
        $quality = ($generation->quality ?? Quality::Medium)->value;
        $referenceImages = $generation->reference_images ?? [];
        $metadata = ['generation_id' => (string) $generation->id];

        return $this->store[$id] = new GenerationData(
            id: $id,
            status: Status::Queued->value,
            prompt: $generation->prompt,
            size: $size,
            quality: $quality,
            referenceImages: $referenceImages,
            metadata: $metadata,
            rates: self::DEFAULT_RATES,
            createdAt: CarbonImmutable::now(),
        );
    }

    public function find(string $generationId): GenerationData
    {
        $existing = $this->store[$generationId] ?? null;
        $prompt = $existing?->prompt ?? 'Mocked prompt';
        $size = Size::tryFrom($existing?->size ?? '') ?? Size::Square;
        $quality = Quality::tryFrom($existing?->quality ?? '') ?? Quality::Medium;
        $referenceImages = $existing?->referenceImages ?? [];
        $metadata = $existing?->metadata ?? [];
        $rates = ! empty($existing?->rates) ? $existing->rates : self::DEFAULT_RATES;
        $createdAt = $existing?->createdAt ?? CarbonImmutable::now()->subSeconds(10);
        $startedAt = $existing?->startedAt ?? $createdAt->addSeconds(2);
        $completedAt = CarbonImmutable::now();

        if (str_contains(strtolower($prompt), '[fail]')) {
            return $this->store[$generationId] = new GenerationData(
                id: $generationId,
                status: Status::Failed->value,
                prompt: $prompt,
                size: $size->value,
                quality: $quality->value,
                referenceImages: $referenceImages,
                metadata: $metadata,
                rates: $rates,
                error: [
                    'code' => 'generation_failed',
                    'message' => 'Simulated failure triggered by [fail] keyword.',
                ],
                createdAt: $createdAt,
                startedAt: $startedAt,
                completedAt: $completedAt,
            );
        }

        $inputTextTokens = (int) ceil(mb_strlen($prompt) / 4);
        $inputImageTokens = count($referenceImages) * 765;
        $outputTokens = $quality->outputTokens($size);
        $reasoningTokens = $quality->simulatedReasoningTokens();

        return $this->store[$generationId] = new GenerationData(
            id: $generationId,
            status: Status::Succeeded->value,
            prompt: $prompt,
            size: $size->value,
            quality: $quality->value,
            referenceImages: $referenceImages,
            metadata: $metadata,
            imageUrl: "https://placehold.co{$size->value}.png?text=".urlencode(Str::limit($prompt, 30)),
            imageUrlExpiresAt: $completedAt->addHours(24),
            usage: [
                'input_text_tokens' => $inputTextTokens,
                'input_image_tokens' => $inputImageTokens,
                'output_tokens' => $outputTokens,
                'reasoning_tokens' => $reasoningTokens,
                'total_tokens' => $inputTextTokens + $inputImageTokens + $outputTokens + $reasoningTokens,
            ],
            rates: $rates,
            createdAt: $createdAt,
            startedAt: $startedAt,
            completedAt: $completedAt,
        );
    }
}
