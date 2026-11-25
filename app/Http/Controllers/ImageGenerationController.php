<?php

namespace App\Http\Controllers;

use App\Actions\ImageGeneration\CreateNewImageGeneration;
use App\Data\ImageGeneration\NewImageGenerationData;
use App\Http\Requests\StoreImageGenerationRequest;
use App\Http\Resources\ImageGenerationResource;
use App\Models\ImageGeneration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Response;

class ImageGenerationController extends Controller implements HasMiddleware
{
    /**
     * @return Middleware[]
     */
    public static function middleware(): array
    {
        return [
            new Middleware('can:create,'.ImageGeneration::class, only: ['create', 'store']),
            new Middleware('can:view,generation', only: ['show']),
        ];
    }

    public function store(StoreImageGenerationRequest $request, CreateNewImageGeneration $action): RedirectResponse
    {
        $newImageGenerationDto = NewImageGenerationData::fromRequest($request);

        $generation = $action->execute($newImageGenerationDto);

        return to_route('generations.show', $generation->ulid);
    }

    public function show(ImageGeneration $generation): Response
    {
        return inertia('generations/Show', [
            'generation' => ImageGenerationResource::make($generation),
        ]);
    }
}
