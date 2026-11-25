<?php

use App\Enums\Quality;
use App\Enums\Size;
use App\Jobs\SubmitImageGeneration;
use App\Models\ImageGeneration;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

use function Pest\Laravel\withoutExceptionHandling;

function newImageGenerationPayload($overrides = []): array
{
    return array_merge([
        'prompt' => 'An epic battle in space',
        'quality' => Quality::High->value,
        'size' => Size::Landscape->value,
        'reference_images' => [
            'https://picsum.photos/id/866/200/300.jpg',
            'https://picsum.photos/id/1062/200/300.jpg',
        ],
        'idempotency_key' => Str::uuid()->toString(),
        'estimated_cost' => '0.000000000',
    ], $overrides);
}

test('can request new image generation', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $payload = newImageGenerationPayload();

    $response = $this->actingAs($user)
        ->post(route('generations.store'), $payload);

    $imageGeneration = ImageGeneration::latest()->first();

    Queue::assertPushed(SubmitImageGeneration::class,
        fn (SubmitImageGeneration $job) => $job->generation->is($imageGeneration)
    );

    $this->assertNotNull($imageGeneration);

    $response->assertRedirectToRoute('generations.show', $imageGeneration->ulid);
});

test('prompt, quality, size, and idempotency_key are required for image generation', function (): void {
    // withoutExceptionHandling();

    $payload = newImageGenerationPayload([
        'prompt' => null,
        'quality' => null,
        'size' => null,
        'idempotency_key' => null,
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('generations.store'), $payload);

    $response->assertInvalid(['prompt', 'quality', 'size', 'idempotency_key']);
});

test('image generation prompt is capped at 4000 characters', function (): void {
    // withoutExceptionHandling();

    $payload = newImageGenerationPayload([
        'prompt' => str_repeat('a', 4001),
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('generations.store'), $payload);

    $response->assertInvalid(['prompt']);
});
