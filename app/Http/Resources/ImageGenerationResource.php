<?php

namespace App\Http\Resources;

use App\Models\ImageGeneration;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ImageGeneration */
class ImageGenerationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->ulid,
            'title' => $this->prompt_title,
            'prompt' => $this->prompt,
            'quality' => $this->quality->value,
            'size' => $this->size->value,
            'status' => $this->status->value,
            'is_in_flight' => $this->isInFlight(),
            'estimated_cost' => $this->estimated_cost
                ? (float) $this->estimated_cost
                : null,
            'provider_cost' => $this->provider_cost !== null ? (float) $this->provider_cost : null,
            'cost' => $this->user_cost !== null ? (float) $this->user_cost : null,
            'image_url' => $this->image_url,
            'is_expired' => $this->image_url_expires_at?->isPast() ?? false,
            'reference_images' => $this->reference_images ?? [],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
