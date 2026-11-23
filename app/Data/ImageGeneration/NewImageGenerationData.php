<?php

namespace App\Data\ImageGeneration;

use App\Enums\Quality;
use App\Enums\Size;
use App\Http\Requests\StoreImageGenerationRequest;
use Illuminate\Support\Str;

class NewImageGenerationData
{
    /**
     * @param array<int, string> $referenceImages
     */
    public function __construct(
        public string $idempotencyKey,
        public string $prompt,
        public Quality $quality,
        public Size $size,
        public array $referenceImages,
        public int $userId
    )
    {}

    public static function fromRequest(StoreImageGenerationRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            Str::lower($validated['idempotency']),
            $validated['prompt'],
            $validated['quality'],
            $validated['size'],
            $validated['reference_images'] ?? [],
            $request->user()->id
        );
    }
}
