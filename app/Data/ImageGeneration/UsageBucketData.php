<?php

declare(strict_types=1);

namespace App\Data\ImageGeneration;

use JsonSerializable;

final readonly class UsageBucketData implements JsonSerializable
{
    public function __construct(
        public ?string $date,
        public int $generations,
        public int $inputTextTokens,
        public int $inputImageTokens,
        public int $outputTokens,
        public int $reasoningTokens,
        public string $costUsd,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            date: $data['date'] ?? null,
            generations: (int) ($data['generations'] ?? 0),
            inputTextTokens: (int) ($data['input_text_tokens'] ?? 0),
            inputImageTokens: (int) ($data['input_image_tokens'] ?? 0),
            outputTokens: (int) ($data['output_tokens'] ?? 0),
            reasoningTokens: (int) ($data['reasoning_tokens'] ?? 0),
            costUsd: (string) ($data['cost_usd'] ?? '0.000000000'),
        );
    }

    public function withMarkup(float|string $multiplier = '1.25'): self
    {
        return new self(
            date: $this->date,
            generations: $this->generations,
            inputTextTokens: $this->inputTextTokens,
            inputImageTokens: $this->inputImageTokens,
            outputTokens: $this->outputTokens,
            reasoningTokens: $this->reasoningTokens,
            costUsd: $this->multiplyUsd($this->costUsd, (string) $multiplier),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'date' => $this->date,
            'generations' => $this->generations,
            'input_text_tokens' => $this->inputTextTokens,
            'input_image_tokens' => $this->inputImageTokens,
            'output_tokens' => $this->outputTokens,
            'reasoning_tokens' => $this->reasoningTokens,
            'billed_usd' => $this->costUsd,
        ], fn (mixed $value): bool => $value !== null);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    private function multiplyUsd(string $costUsd, string $multiplier): string
    {
        if (function_exists('bcmul')) {
            return bcmul($costUsd, $multiplier, 9);
        }

        // Exact integer nanodollar fallback for 1.25 (25% markup = * 5 / 4)
        [$whole, $fraction] = array_pad(explode('.', $costUsd, 2), 2, '0');
        $nanodollars = ((int) $whole * 1_000_000_000) + (int) str_pad(substr($fraction, 0, 9), 9, '0');
        $basisPoints = (int) round((float) $multiplier * 10_000);
        $markedUpNano = intdiv($nanodollars * $basisPoints, 10_000);

        return sprintf(
            '%d.%09d',
            intdiv($markedUpNano, 1_000_000_000),
            $markedUpNano % 1_000_000_000
        );
    }
}
