<?php

namespace App\Services\ImageGeneration;

use App\Contracts\ImageGenerator;
use App\Data\ImageGeneration\GenerationData;
use App\Data\ImageGeneration\UsageBucketData;
use App\Data\ImageGeneration\UsageData;
use App\Enums\Quality;
use App\Enums\Size;
use App\Enums\Status;
use App\Models\ImageGeneration;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
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
            imageUrl: "https://placehold.co/{$size->value}.png?text=".urlencode(Str::limit($prompt, 30)),
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

    public function usage(?string $startDate = null, ?string $endDate = null): UsageData
    {
        $end = $endDate !== null
            ? CarbonImmutable::createFromFormat('Y-m-d', $endDate, 'UTC')->startOfDay()
            : CarbonImmutable::now('UTC')->startOfDay();

        $start = $startDate !== null
            ? CarbonImmutable::createFromFormat('Y-m-d', $startDate, 'UTC')->startOfDay()
            : $end->subDays(29);

        /** @var array<string, array{generations: int, input_text: int, input_image: int, output: int, reasoning: int, cost_usd: string}> $storedByDate */
        $storedByDate = [];

        foreach ($this->store as $generation) {
            if ($generation->status !== Status::Succeeded->value || $generation->completedAt === null || empty($generation->usage)) {
                continue;
            }

            $utcDate = $generation->completedAt->setTimezone('UTC')->format('Y-m-d');
            $storedByDate[$utcDate] ??= [
                'generations' => 0,
                'input_text' => 0,
                'input_image' => 0,
                'output' => 0,
                'reasoning' => 0,
                'cost_usd' => '0.000000000',
            ];

            $inputText = (int) ($generation->usage['input_text_tokens'] ?? 0);
            $inputImage = (int) ($generation->usage['input_image_tokens'] ?? 0);
            $output = (int) ($generation->usage['output_tokens'] ?? 0);
            $reasoning = (int) ($generation->usage['reasoning_tokens'] ?? 0);

            $generationCost = $this->calculateCostUsd(
                $inputText,
                $inputImage,
                $output,
                $reasoning,
                ! empty($generation->rates) ? $generation->rates : self::DEFAULT_RATES,
            );

            $storedByDate[$utcDate]['generations']++;
            $storedByDate[$utcDate]['input_text'] += $inputText;
            $storedByDate[$utcDate]['input_image'] += $inputImage;
            $storedByDate[$utcDate]['output'] += $output;
            $storedByDate[$utcDate]['reasoning'] += $reasoning;
            $storedByDate[$utcDate]['cost_usd'] = bcadd($storedByDate[$utcDate]['cost_usd'], $generationCost, 9);
        }

        $days = [];
        $totalGenerations = 0;
        $totalInputText = 0;
        $totalInputImage = 0;
        $totalOutput = 0;
        $totalReasoning = 0;
        $totalCostUsd = '0.000000000';

        foreach (CarbonPeriod::create($start, '1 day', $end) as $date) {
            $dateString = $date->format('Y-m-d');

            if (isset($storedByDate[$dateString])) {
                $bucket = $storedByDate[$dateString];
            } else {
                $seed = crc32($dateString);
                $generations = ($seed % 5 === 0) ? 0 : ($seed % 6) + 1;

                if ($generations === 0) {
                    $bucket = [
                        'generations' => 0,
                        'input_text' => 0,
                        'input_image' => 0,
                        'output' => 0,
                        'reasoning' => 0,
                        'cost_usd' => '0.000000000',
                    ];
                } else {
                    $inputText = $generations * (12 + ($seed % 25));
                    $inputImage = ($seed % 3 === 0) ? 765 : 0;
                    $output = $generations * 1100;
                    $reasoning = $generations * (400 + ($seed % 900));

                    $bucket = [
                        'generations' => $generations,
                        'input_text' => $inputText,
                        'input_image' => $inputImage,
                        'output' => $output,
                        'reasoning' => $reasoning,
                        'cost_usd' => $this->calculateCostUsd(
                            $inputText,
                            $inputImage,
                            $output,
                            $reasoning,
                            self::DEFAULT_RATES,
                        ),
                    ];
                }
            }

            $totalGenerations += $bucket['generations'];
            $totalInputText += $bucket['input_text'];
            $totalInputImage += $bucket['input_image'];
            $totalOutput += $bucket['output'];
            $totalReasoning += $bucket['reasoning'];
            $totalCostUsd = bcadd($totalCostUsd, $bucket['cost_usd'], 9);

            $days[] = new UsageBucketData(
                date: $dateString,
                generations: $bucket['generations'],
                inputTextTokens: $bucket['input_text'],
                inputImageTokens: $bucket['input_image'],
                outputTokens: $bucket['output'],
                reasoningTokens: $bucket['reasoning'],
                costUsd: $bucket['cost_usd'],
            );
        }

        return new UsageData(
            startDate: $start->format('Y-m-d'),
            endDate: $end->format('Y-m-d'),
            data: $days,
            total: new UsageBucketData(
                date: null,
                generations: $totalGenerations,
                inputTextTokens: $totalInputText,
                inputImageTokens: $totalInputImage,
                outputTokens: $totalOutput,
                reasoningTokens: $totalReasoning,
                costUsd: $totalCostUsd,
            ),
        );
    }

    /**
     * @param  array<string, string>  $rates  USD per 1M tokens
     */
    protected function calculateCostUsd(
        int $inputTextTokens,
        int $inputImageTokens,
        int $outputTokens,
        int $reasoningTokens,
        array $rates,
    ): string {
        $textCost = bcmul((string) $inputTextTokens, $rates['input_text_tokens'] ?? '5.00', 9);
        $imageCost = bcmul((string) $inputImageTokens, $rates['input_image_tokens'] ?? '10.00', 9);
        $outputCost = bcmul((string) $outputTokens, $rates['output_tokens'] ?? '40.00', 9);
        $reasoningCost = bcmul((string) $reasoningTokens, $rates['reasoning_tokens'] ?? '12.50', 9);

        $costPerMillion = bcadd(
            bcadd($textCost, $imageCost, 9),
            bcadd($outputCost, $reasoningCost, 9),
            9
        );

        return bcdiv($costPerMillion, '1000000', 9);
    }

}
