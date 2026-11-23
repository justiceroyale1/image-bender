<?php

use App\Enums\Quality;
use App\Enums\Size;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\AssertableJson;

function newImageGenerationPayload($overrides = []){
    return array_merge([
        'prompt' => fake()->sentences(2, true),
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
    $payload = newImageGenerationPayload();

    $response = $this->post(route('generations.store'), $payload);

    $response->assertOk()
        ->assertJson(fn (AssertableJson $json) => 
            $json->where('quality', $payload['quality'])
                ->where('size', $payload['size'])
                ->where('idempotency_key', $payload['idempotency_key'])
                ->etc()
        );
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
