<?php

namespace App\Data\ImageGeneration;

use App\Http\Requests\StoreImageGenerationRequest;
use Illuminate\Support\Str;

class NewImageGenerationData
{
    /**
     * @param  array<int, string>  $referenceImages
     */
    public function __construct(
        public string $idempotencyKey,
        public string $prompt,
        public string $quality,
        public string $size,
        public array $referenceImages,
        public int $userId
    ) {}

    public static function fromRequest(StoreImageGenerationRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            Str::lower($validated['idempotency_key']),
            $validated['prompt'],
            $validated['quality'],
            $validated['size'],
            $validated['reference_images'] ?? [],
            $request->user()->id
        );
    }
}
