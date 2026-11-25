<?php

use App\Contracts\ImageGenerator;
use App\Enums\Quality;
use App\Enums\Size;
use App\Enums\Status;
use App\Jobs\SubmitImageGeneration;
use App\Models\ImageGeneration;
use App\Models\User;
use App\Services\ImageGeneration\MockImageGenerator;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

test('submit image generation job uses mock driver and updates model', function (): void {
    expect(resolve(ImageGenerator::class))->toBeInstanceOf(MockImageGenerator::class);

    $generation = ImageGeneration::factory()->create([
        'prompt' => 'A lighthouse on a cliff at dawn',
        'quality' => Quality::High,
        'size' => Size::Landscape,
    ]);

    dispatch_sync(new SubmitImageGeneration($generation));

    $generation->refresh();

    expect($generation->status)->toBe(Status::Queued)
        ->and($generation->provider_id)->toStartWith('gen_mock_');
});

test('submit image generation job marks generation failed on non-retryable api error', function (): void {
    config()->set('ai-image.driver', 'pixelforge');
    config()->set('ai-image.base_url', 'https://promptedit.dev');
    config()->set('ai-image.api_key', 'test-key');

    // Clear any cached MockImageGenerator singleton before resolving the job
    app()->forgetInstance(ImageGenerator::class);

    Http::fake([
        'https://promptedit.dev/generations' => Http::response([
            'error' => [
                'type' => 'invalid_request_error',
                'code' => 'content_policy_violation',
                'message' => 'The prompt was rejected.',
            ],
        ], 400),
    ]);

    $generation = ImageGeneration::create([
        'user_id' => User::factory()->create()->id,
        'idempotency_key' => 'idem-blocked',
        'prompt' => '[blocked] unsafe prompt',
        'quality' => Quality::Medium,
        'size' => Size::Square,
    ]);

    $job = (new SubmitImageGeneration($generation))->withFakeQueueInteractions();

    try {
        app()->call($job->handle(...));
    } catch (RequestException) {
        // Expected when $this->fail($e) is called inside handle()
    }

    $generation->refresh();

    expect($generation->status)->toBe(Status::Failed)
        ->and($generation->provider_error)->toMatchArray([
            'code' => 'content_policy_violation',
        ]);
});

test('submit image generation job releases back to queue on 429 rate limit', function (): void {
    config()->set('ai-image.driver', 'pixelforge');
    config()->set('ai-image.base_url', 'https://promptedit.dev');
    config()->set('ai-image.api_key', 'test-key');
    app()->forgetInstance(ImageGenerator::class);

    Http::preventStrayRequests();

    Http::fake([
        'https://promptedit.dev/generations' => Http::response([
            'error' => [
                'type' => 'rate_limit_error',
                'code' => 'rate_limit_exceeded',
                'message' => 'Too many requests this minute.',
            ],
        ], 429, ['Retry-After' => '5']),
    ]);

    $generation = ImageGeneration::factory()->create();

    $job = (new SubmitImageGeneration($generation))->withFakeQueueInteractions();

    app()->call($job->handle(...));

    $job->assertReleased(delay: 5);
    expect($generation->fresh()->status)->toBe(Status::Queued);
});
