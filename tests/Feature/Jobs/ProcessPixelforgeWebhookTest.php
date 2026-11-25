
<?php

use App\Enums\Status;
use App\Jobs\ProcessPixelforgeWebhook;
use App\Models\ImageGeneration;
use App\Models\WebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

// -----------------------------------------------------------------------------
// Helpers
// -----------------------------------------------------------------------------

function createPixelforgeWebhookEvent(
    string $type,
    array $object = [],
    string $eventId = 'evt_test_123',
): WebhookEvent {
    return WebhookEvent::create([
        'provider' => 'pixelforge',
        'event_id' => $eventId,
        'type' => $type,
        'payload' => [
            'id' => $eventId,
            'type' => $type,
            'data' => [
                'object' => $object,
            ],
        ],
    ]);
}

function createImageGeneration(
    string $providerId = 'gen_test_123',
    array $attributes = [],
): ImageGeneration {
    return ImageGeneration::factory()->create(array_merge([
        'provider_id' => $providerId,
    ], $attributes));
}

function processPixelforgeWebhook(WebhookEvent $event): ProcessPixelforgeWebhook
{
    $job = new ProcessPixelforgeWebhook($event);

    $job->handle();

    return $job;
}

// -----------------------------------------------------------------------------
// Status updates
// -----------------------------------------------------------------------------

describe('generation status updates', function (): void {
    test('updates generation status when a processing event is received', function (): void {
        $generation = createImageGeneration();

        $event = createPixelforgeWebhookEvent(
            'generation.processing',
            [
                'id' => $generation->provider_id,
                'status' => 'processing',
                'progress' => 50,
            ]
        );

        processPixelforgeWebhook($event);

        $generation->refresh();
        $event->refresh();

        expect($generation->status)->toBe(Status::Processing)
            ->and($generation->provider_response)->toMatchArray([
                'id' => $generation->provider_id,
                'status' => 'processing',
                'progress' => 50,
            ])
            ->and($generation->provider_cost)->toBeNull()
            ->and($event->processed_at)->not->toBeNull();
    });

    test('updates generation status when a failed event is received', function (): void {
        $generation = createImageGeneration();

        $providerError = [
            'code' => 'generation_failed',
            'message' => 'Image generation failed.',
        ];

        $event = createPixelforgeWebhookEvent(
            'generation.failed',
            [
                'id' => $generation->provider_id,
                'status' => 'failed',
                'error' => $providerError,
            ]
        );

        processPixelforgeWebhook($event);

        $generation->refresh();
        $event->refresh();

        expect($generation->status)->toBe(Status::Failed)
            ->and($generation->provider_error)->toEqual($providerError)
            ->and($generation->provider_response)->toMatchArray([
                'id' => $generation->provider_id,
                'status' => 'failed',
                'error' => $providerError,
            ])
            ->and($generation->provider_cost)->toBeNull()
            ->and($event->processed_at)->not->toBeNull();
    });

    test('marks events without a provider ID as processed', function (): void {
        $event = createPixelforgeWebhookEvent(
            'generation.processing',
            []
        );

        processPixelforgeWebhook($event);

        $event->refresh();

        expect($event->processed_at)->not->toBeNull();

        expect(ImageGeneration::count())->toBe(0);
    });

    test('marks unsupported event types as processed', function (): void {
        $generation = createImageGeneration();

        $event = createPixelforgeWebhookEvent(
            'generation.unknown',
            [
                'id' => $generation->provider_id,
            ]
        );

        processPixelforgeWebhook($event);

        $event->refresh();
        $generation->refresh();

        expect($event->processed_at)->not->toBeNull()
            ->and($generation->status)->not->toBe(Status::Succeeded);
    });
});

// -----------------------------------------------------------------------------
// Successful generation and file storage
// -----------------------------------------------------------------------------

describe('successful generation', function (): void {
    test('downloads and stores the generated image on the public disk', function (): void {
        Storage::fake('public');

        config([
            'ai-image.storage_disk' => 'public',
        ]);

        $generation = createImageGeneration();

        $imageContents = 'fake-png-image-content';
        $remoteUrl = 'https://cdn.pixelforge.test/generated/image.png';

        Http::fake([
            $remoteUrl => Http::response($imageContents, 200),
        ]);

        $event = createPixelforgeWebhookEvent(
            'generation.succeeded',
            [
                'id' => $generation->provider_id,
                'status' => 'succeeded',
                'image_url' => $remoteUrl,
                'image_url_expires_at' => now()->addHour()->toISOString(),
                'usage' => [
                    'input_text_tokens' => 1000000,
                    'input_image_tokens' => 500000,
                    'output_tokens' => 250000,
                    'reasoning_tokens' => 100000,
                ],
                'rates' => [
                    'input_text_tokens' => '0.5',
                    'input_image_tokens' => '1.25',
                    'output_tokens' => '2',
                    'reasoning_tokens' => '0.75',
                ],
            ]
        );

        processPixelforgeWebhook($event);

        $generation->refresh();
        $event->refresh();

        $path = "generations/{$generation->ulid}.png";

        Storage::disk('public')->assertExists($path);

        expect(Storage::disk('public')->get($path))
            ->toBe($imageContents);

        expect($generation->status)->toBe(Status::Succeeded)
            ->and($generation->image_url)->toBe(
                url(Storage::disk('public')->url($path))
            )
            ->and($generation->image_url_expires_at)->toBeNull()
            ->and($generation->provider_cost)->toBe('1.700000000')
            ->and($generation->provider_response['status'])->toBe('succeeded')
            ->and($event->processed_at)->not->toBeNull();

        Http::assertSent(
            fn ($request): bool => $request->url() === $remoteUrl
        );
    });

    test('stores the generated image on an S3-compatible disk', function (): void {
        // Create a fake disk first, then configure its metadata as S3.
        // The resolved fake disk remains in use by Laravel's filesystem manager.
        Storage::fake('s3');

        config([
            'ai-image.storage_disk' => 's3',
            'filesystems.disks.s3' => [
                'driver' => 's3',
                'key' => 'test-access-key',
                'secret' => 'test-secret-key',
                'region' => 'us-east-1',
                'bucket' => 'test-bucket',
            ],
        ]);

        $generation = createImageGeneration();

        $remoteUrl = 'https://cdn.pixelforge.test/generated/s3-image.png';
        $imageContents = 'fake-s3-png-content';

        Http::fake([
            $remoteUrl => Http::response($imageContents, 200),
        ]);

        $event = createPixelforgeWebhookEvent(
            'generation.succeeded',
            [
                'id' => $generation->provider_id,
                'image_url' => $remoteUrl,
                'usage' => [],
                'rates' => [],
            ]
        );

        processPixelforgeWebhook($event);

        $generation->refresh();

        $path = "generations/{$generation->ulid}.png";

        Storage::disk('s3')->assertExists($path);

        expect($generation->status)->toBe(Status::Succeeded)
            ->and($generation->image_url)->toBe(
                url(Storage::disk('s3')->url($path))
            )
            ->and($generation->image_url_expires_at)->toBeNull()
            ->and($generation->provider_cost)->toBe('0.000000000');
    });

    test('falls back to the remote image URL when storage is not configured', function (): void {
        config([
            'ai-image.storage_disk' => null,
        ]);

        $generation = createImageGeneration();

        $remoteUrl = 'https://cdn.pixelforge.test/generated/remote-image.png';

        Http::fake();

        $expiresAt = now()->addHour()->toISOString();

        $event = createPixelforgeWebhookEvent(
            'generation.succeeded',
            [
                'id' => $generation->provider_id,
                'image_url' => $remoteUrl,
                'image_url_expires_at' => $expiresAt,
                'usage' => [],
                'rates' => [],
            ]
        );

        processPixelforgeWebhook($event);

        $generation->refresh();

        expect($generation->status)->toBe(Status::Succeeded)
            ->and($generation->image_url)->toBe($remoteUrl)
            ->and($generation->image_url_expires_at)->not->toBeNull();

        Http::assertNothingSent();
    });

    test('falls back to the remote URL when image download fails', function (): void {
        Storage::fake('public');

        config([
            'ai-image.storage_disk' => 'public',
        ]);

        $generation = createImageGeneration();

        $remoteUrl = 'https://cdn.pixelforge.test/generated/unavailable.png';

        Http::fake([
            $remoteUrl => Http::response('Image unavailable', 404),
        ]);

        $expiresAt = now()->addHour()->toISOString();

        $event = createPixelforgeWebhookEvent(
            'generation.succeeded',
            [
                'id' => $generation->provider_id,
                'image_url' => $remoteUrl,
                'image_url_expires_at' => $expiresAt,
                'usage' => [],
                'rates' => [],
            ]
        );

        processPixelforgeWebhook($event);

        $generation->refresh();

        expect($generation->status)->toBe(Status::Succeeded)
            ->and($generation->image_url)->toBe($remoteUrl)
            ->and($generation->image_url_expires_at)->not->toBeNull();

        Storage::disk('public')->assertMissing(
            "generations/{$generation->ulid}.png"
        );
    });

    test('does not download an image when the remote URL is missing', function (): void {
        Storage::fake('public');

        config([
            'ai-image.storage_disk' => 'public',
        ]);

        $generation = createImageGeneration();

        Http::fake();

        $event = createPixelforgeWebhookEvent(
            'generation.succeeded',
            [
                'id' => $generation->provider_id,
                'usage' => [],
                'rates' => [],
            ]
        );

        processPixelforgeWebhook($event);

        $generation->refresh();

        expect($generation->status)->toBe(Status::Succeeded)
            ->and($generation->image_url)->toBeNull()
            ->and($generation->provider_cost)->toBe('0.000000000');

        Http::assertNothingSent();
    });
});

// -----------------------------------------------------------------------------
// Exact cost calculations
// -----------------------------------------------------------------------------

describe('provider cost calculation', function (): void {
    test('calculates exact token costs without floating-point drift', function (): void {
        $generation = createImageGeneration();

        $event = createPixelforgeWebhookEvent(
            'generation.succeeded',
            [
                'id' => $generation->provider_id,
                'usage' => [
                    'input_text_tokens' => 1000000,
                    'input_image_tokens' => 500000,
                    'output_tokens' => 250000,
                    'reasoning_tokens' => 100000,
                ],
                'rates' => [
                    'input_text_tokens' => '0.5',
                    'input_image_tokens' => '1.25',
                    'output_tokens' => '2',
                    'reasoning_tokens' => '0.75',
                ],
            ]
        );

        processPixelforgeWebhook($event);

        $generation->refresh();

        expect($generation->provider_cost)->toBe('1.700000000');
    });

    test('calculates zero cost when usage and rates are missing', function (): void {
        $generation = createImageGeneration();

        $event = createPixelforgeWebhookEvent(
            'generation.succeeded',
            [
                'id' => $generation->provider_id,
            ]
        );

        processPixelforgeWebhook($event);

        $generation->refresh();

        expect($generation->provider_cost)->toBe('0.000000000');
    });

    test('ignores missing or non-numeric rates', function (): void {
        $generation = createImageGeneration();

        $event = createPixelforgeWebhookEvent(
            'generation.succeeded',
            [
                'id' => $generation->provider_id,
                'usage' => [
                    'input_text_tokens' => 1000000,
                    'output_tokens' => 500000,
                ],
                'rates' => [
                    'input_text_tokens' => '0.25',
                    'output_tokens' => 'invalid',
                ],
            ]
        );

        processPixelforgeWebhook($event);

        $generation->refresh();

        expect($generation->provider_cost)->toBe('0.250000000');
    });
});

// -----------------------------------------------------------------------------
// Out-of-order webhook handling
// -----------------------------------------------------------------------------

describe('out-of-order webhook handling', function (): void {
    test('ignores processing events when the generation has already succeeded', function (): void {
        $generation = createImageGeneration(
            attributes: [
                'status' => Status::Succeeded,
                'image_url' => 'https://cdn.pixelforge.test/completed.png',
            ]
        );

        $originalImageUrl = $generation->image_url;

        $event = createPixelforgeWebhookEvent(
            'generation.processing',
            [
                'id' => $generation->provider_id,
                'status' => 'processing',
            ]
        );

        processPixelforgeWebhook($event);

        $generation->refresh();
        $event->refresh();

        expect($generation->status)->toBe(Status::Succeeded)
            ->and($generation->image_url)->toBe($originalImageUrl)
            ->and($event->processed_at)->not->toBeNull();
    });

    test('ignores failed events when the generation has already succeeded', function (): void {
        $generation = createImageGeneration(
            attributes: [
                'status' => Status::Succeeded,
                'image_url' => 'https://cdn.pixelforge.test/completed.png',
            ]
        );

        $event = createPixelforgeWebhookEvent(
            'generation.failed',
            [
                'id' => $generation->provider_id,
                'error' => [
                    'message' => 'Late failure notification.',
                ],
            ]
        );

        processPixelforgeWebhook($event);

        $generation->refresh();
        $event->refresh();

        expect($generation->status)->toBe(Status::Succeeded)
            ->and($generation->provider_error)->toBeNull()
            ->and($event->processed_at)->not->toBeNull();
    });
});

// -----------------------------------------------------------------------------
// Idempotency
// -----------------------------------------------------------------------------

describe('webhook idempotency', function (): void {
    test('does not process an already processed webhook again', function (): void {
        $generation = createImageGeneration();

        $event = createPixelforgeWebhookEvent(
            'generation.processing',
            [
                'id' => $generation->provider_id,
            ]
        );

        $event->update([
            'processed_at' => now(),
        ]);

        processPixelforgeWebhook($event);

        $generation->refresh();

        expect($generation->status)->not->toBe(Status::Processing);
    });
});

// -----------------------------------------------------------------------------
// Race condition and queue retries
// -----------------------------------------------------------------------------

describe('generation creation race condition', function (): void {
    test('releases the job with a 2-second delay when the generation does not exist yet', function (): void {
        $event = createPixelforgeWebhookEvent(
            'generation.processing',
            [
                'id' => 'gen_not_saved_yet',
            ]
        );

        expect(ImageGeneration::where(
            'provider_id',
            'gen_not_saved_yet'
        )->exists())->toBeFalse();

        $job = new ProcessPixelforgeWebhook($event);

        $job->withFakeQueueInteractions();

        $job->handle();

        $job->assertReleased(2);

        $event->refresh();

        expect($event->processed_at)->toBeNull();
    });

    test('does not mark the webhook as processed when all retry attempts are exhausted', function (): void {
        $event = createPixelforgeWebhookEvent(
            'generation.processing',
            [
                'id' => 'gen_still_not_saved',
            ]
        );

        $job = new ProcessPixelforgeWebhook($event);

        $job->withFakeQueueInteractions();

        // FakeJob starts on attempt 1; setting tries = 1 simulates the final attempt.
        $job->tries = 1;

        $job->handle();

        $job->assertNotReleased();

        $event->refresh();

        expect($event->processed_at)->toBeNull();
    });
});
