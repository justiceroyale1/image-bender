<?php

use App\Enums\Quality;
use App\Enums\Size;
use App\Models\ImageGeneration;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\withoutExceptionHandling;

function newImageGenerationPayload($overrides = []){
    return array_merge([
        'prompt' => "An epic battle in space",
        'quality' => Quality::High->value,
        'size' => Size::Landscape->value,
        'reference_images' => [
            'https://picsum.photos/id/866/200/300.jpg',
            'https://picsum.photos/id/1062/200/300.jpg',
        ],
        'idempotency_key' => Str::uuid()->toString(),
    ], $overrides);
}

test('can request new image generation', function () {
    withoutExceptionHandling();

    $user = User::factory()->create();
    $payload = newImageGenerationPayload();

    $response = $this->actingAs($user)
        ->post(route('generations.store'), $payload);

    /**
     * @todo: assert the Pixelforge job was dispatched
     */

    $imageGeneration = ImageGeneration::latest()->first();

    $this->assertNotNull($imageGeneration);

    $response->assertRedirectToRoute('generations.show', $imageGeneration->ulid);
});

test('prompt, quality, size, and idempotency_key are required for image generation', function () {
    $payload = newImageGenerationPayload([
        'prompt' => null,
        'quality' => null,
        'size' => null,
        'idempotency_key' => null
    ]);

    $response = $this->post(route('generations.store'), $payload);

    $response->assertInvalid(['prompt', 'quality', 'size', 'idempotency_key']);
});
