<?php

use App\Contracts\ImageGenerator;
use App\Enums\Quality;
use App\Enums\Size;
use App\Enums\Status;
use App\Models\ImageGeneration;
use App\Services\ImageGeneration\MockImageGenerator;
use App\Services\ImageGeneration\PixelforgeImageGenerator;
use Illuminate\Support\Facades\Http;

test('container resolves mock image generator by default in tests', function () {
    expect(app(ImageGenerator::class))->toBeInstanceOf(MockImageGenerator::class);
});

test('mock image generator simulates lifecycle and fail keyword', function () {
    $generator = new MockImageGenerator();

    $successModel = ImageGeneration::factory()->create([
        'prompt' => 'A peaceful mountain lake',
        'quality' => Quality::High,
        'size' => Size::Landscape,
    ]);

    $queued = $generator->generate($successModel);
    expect($queued->status)->toBe(Status::Queued->value)
        ->and($queued->id)->toStartWith('gen_mock_');

    $failModel = ImageGeneration::factory()->create([
        'prompt' => 'Test [fail] prompt',
        'quality' => Quality::Low,
        'size' => Size::Square,
    ]);

    $failedQueued = $generator->generate($failModel);
    $failedResult = $generator->find($failedQueued->id);

    expect($failedResult->status)->toBe(Status::Failed->value);
});

test('pixelforge image generator sends expected payload and headers', function () {
    Http::fake([
        'https://promptedit.dev/generations' => Http::response([
            'id' => 'gen_01JC2Z8Q4N6V0M3H7X5T9R2B1K',
            'status' => Status::Queued->value,
            'prompt' => 'A lighthouse on a cliff',
            'size' => Size::Landscape->value,
            'quality' => Quality::High->value,
        ], 202),
    ]);

    $generator = new PixelforgeImageGenerator(
        baseUrl: 'https://promptedit.dev',
        apiKey: 'pf_test_secret',
    );

    $model = ImageGeneration::factory()->create([
        'idempotency_key' => 'uuid-999',
        'prompt' => 'A lighthouse on a cliff',
        'size' => Size::Landscape,
        'quality' => Quality::High,
    ]);

    $result = $generator->generate($model);

    expect($result->id)->toBe('gen_01JC2Z8Q4N6V0M3H7X5T9R2B1K')
        ->and($result->status)->toBe(Status::Queued->value);

    Http::assertSent(function ($request) use ($model) {
        return $request->url() === 'https://promptedit.dev/generations'
            && $request->hasHeader('Authorization', 'Bearer pf_test_secret')
            && $request->hasHeader('Idempotency-Key', 'uuid-999')
            && $request['prompt'] === 'A lighthouse on a cliff'
            && $request['size'] === '1536x1024'
            && $request['quality'] === 'high'
            && $request['metadata'] === ['generation_id' => (string) $model->id];
    });
});
