<?php

declare(strict_types=1);

namespace App\Enums;

enum Quality: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public function outputTokens(Size $size): int
    {
        return match ($this) {
            self::Low => $size->isWideOrTall() ? 420 : 280,
            self::Medium => $size->isWideOrTall() ? 1650 : 1100,
            self::High => $size->isWideOrTall() ? 6450 : 4300,
        };
    }

    /**
     * @return array{min: int, max: int}
     */
    public function reasoningRange(): array
    {
        return match ($this) {
            self::Low => [ReasoningLevel::Min->value => 0, ReasoningLevel::Max->value => 500],
            self::Medium => [ReasoningLevel::Min->value => 200, ReasoningLevel::Max->value => 2000],
            self::High => [ReasoningLevel::Min->value => 1000, ReasoningLevel::Max->value => 8000],
        };
    }

    public function simulatedReasoningTokens(): int
    {
        $range = $this->reasoningRange();

        return intdiv($range[ReasoningLevel::Min->value] + $range[ReasoningLevel::Max->value], 2);
    }
}
