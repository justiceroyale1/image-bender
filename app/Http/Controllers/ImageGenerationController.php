<?php

namespace App\Http\Controllers;

use App\Actions\ImageGeneration\CreateNewImageGeneration;
use App\Data\ImageGeneration\NewImageGenerationData;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreImageGenerationRequest;
use Illuminate\Http\RedirectResponse;

class ImageGenerationController extends Controller
{
    public function store(StoreImageGenerationRequest $request, CreateNewImageGeneration $action): RedirectResponse
    {
        $newImageGenerationDto = NewImageGenerationData::fromRequest($request);

        $generation = $action->execute($newImageGenerationDto);

        return redirect(route('generations.show', $generation->id));
    }
}
