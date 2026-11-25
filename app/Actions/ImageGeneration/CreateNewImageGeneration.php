<?php

namespace App\Actions\ImageGeneration;

use App\Data\ImageGeneration\NewImageGenerationData;
use App\Jobs\SubmitImageGeneration;
use App\Models\ImageGeneration;

class CreateNewImageGeneration
{
    public function execute(NewImageGenerationData $data): ImageGeneration
    {
        $generation = ImageGeneration::firstOrCreate([
            'user_id' => $data->userId,
            'idempotency_key' => $data->idempotencyKey,
        ], [
            'prompt' => $data->prompt,
            'quality' => $data->quality,
            'size' => $data->size,
            'reference_images' => $data->referenceImages,
        ]);

        if ($generation->wasRecentlyCreated || $generation->dispatched_at === null) {
            dispatch(new SubmitImageGeneration($generation))->afterCommit();
            $generation->update(['dispatched_at' => now()]);
        }

        return $generation;
    }
}
