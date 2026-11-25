<?php

declare(strict_types=1);

namespace App\Data\ImageGeneration;

use JsonSerializable;

final readonly class UsageData implements JsonSerializable
{
    /**
     * @param  array<int, UsageBucketData>  $data
     */
    public function __construct(
        public string $startDate,
        public string $endDate,
        public array $data,
        public UsageBucketData $total,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            startDate: (string) $payload['start_date'],
            endDate: (string) $payload['end_date'],
            data: array_map(
                UsageBucketData::fromArray(...),
                $payload['data'] ?? []
            ),
            total: UsageBucketData::fromArray($payload['total'] ?? []),
        );
    }

    public function withMarkup(float|string $multiplier = '1.25'): self
    {
        return new self(
            startDate: $this->startDate,
            endDate: $this->endDate,
            data: array_map(
                fn (UsageBucketData $day): UsageBucketData => $day->withMarkup($multiplier),
                $this->data
            ),
            total: $this->total->withMarkup($multiplier),
        );
    }

    /**
     * @return array<string, mixed[]|string>
     */
    public function toArray(): array
    {
        return [
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'data' => array_map(fn (UsageBucketData $day): array => $day->toArray(), $this->data),
            'total' => $this->total->toArray(),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
