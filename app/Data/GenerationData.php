<?php

namespace App\Data;

use App\Enums\Quality;
use App\Enums\Size;
use Carbon\CarbonImmutable;

readonly class GenerationData
{
    /**
     * @param  array<int, string>  $referenceImages
     * @param  array<string, string>  $metadata
     * @param  array{input_text_tokens: string, input_image_tokens: string, output_tokens: string, reasoning_tokens: string}  $rates
     * @param  array{input_text_tokens: int, input_image_tokens: int, output_tokens: int, reasoning_tokens: int, total_tokens: int}|null  $usage
     * @param  array{code: string, message: string}|null  $error
     */
    public function __construct(
        public string $id,
        public string $status, // queued, processing, succeeded, failed
        public string $prompt,
        public string $size = Size::Square->value,
        public string $quality = Quality::Medium->value,
        public array $referenceImages = [],
        public array $metadata = [],
        public ?string $imageUrl = null,
        public ?CarbonImmutable $imageUrlExpiresAt = null,
        public ?array $usage = null,
        public array $rates = [],
        public ?array $error = null,
        public ?CarbonImmutable $createdAt = null,
        public ?CarbonImmutable $startedAt = null,
        public ?CarbonImmutable $completedAt = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            status: $data['status'],
            prompt: $data['prompt'],
            size: $data['size'] ?? Size::Square->value,
            quality: $data['quality'] ?? Quality::Medium->value,
            referenceImages: $data['reference_images'] ?? [],
            metadata: $data['metadata'] ?? [],
            imageUrl: $data['image_url'] ?? null,
            imageUrlExpiresAt: isset($data['image_url_expires_at'])
                ? CarbonImmutable::parse($data['image_url_expires_at'])
                : null,
            usage: $data['usage'] ?? null,
            rates: $data['rates'] ?? [],
            error: $data['error'] ?? null,
            createdAt: isset($data['created_at'])
                ? CarbonImmutable::parse($data['created_at'])
                : null,
            startedAt: isset($data['started_at'])
                ? CarbonImmutable::parse($data['started_at'])
                : null,
            completedAt: isset($data['completed_at'])
                ? CarbonImmutable::parse($data['completed_at'])
                : null,
        );
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, ['succeeded', 'failed'], true);
    }

    public function succeeded(): bool
    {
        return $this->status === 'succeeded';
    }

    public function failed(): bool
    {
        return $this->status === 'failed';
    }
}
