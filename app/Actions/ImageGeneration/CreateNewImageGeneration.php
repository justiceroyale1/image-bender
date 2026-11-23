<?php

namespace App\Actions\ImageGeneration;

use App\Data\ImageGeneration\NewImageGenerationData;
use App\Models\ImageGeneration;

class CreateNewImageGeneration{
    public function execute(NewImageGenerationData $data): ImageGeneration
    {
        $generation = ImageGeneration::firstOrCreate([
            'user_id' => $data->userId,
            'idempotency_key' => $data->idempotencyKey
        ], [
            'prompt' => $data->prompt,
            'quality' => $data->quality,
            'size' => $data->size,
            'reference_images' => $data->referenceImages,
        ]);

        if($generation->wasRecentlyCreated){
            //@todo: dispatch Pixelforge API job
        }

        return $generation;
    }
}