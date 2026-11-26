<?php

use App\Contracts\ImageGenerator;
use App\Enums\Quality;
use App\Enums\Size;
use App\Enums\Status;
use App\Models\ImageGeneration;
use App\Services\ImageGeneration\MockImageGenerator;
use App\Services\ImageGeneration\PixelforgeImageGenerator;
use Illuminate\Support\Facades\Http;
use App\Data\ImageGeneration\UsageData;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;


test('container resolves mock image generator by default in tests', function (): void {
    expect(resolve(ImageGenerator::class))->toBeInstanceOf(MockImageGenerator::class);
});

test('mock image generator simulates lifecycle and fail keyword', function (): void {
    $generator = new MockImageGenerator;

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

test('pixelforge image generator sends expected payload and headers', function (): void {
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

    Http::assertSent(fn ($request): bool => $request->url() === 'https://promptedit.dev/generations'
        && $request->hasHeader('Authorization', 'Bearer pf_test_secret')
        && $request->hasHeader('Idempotency-Key', 'uuid-999')
        && $request['prompt'] === 'A lighthouse on a cliff'
        && $request['size'] === '1536x1024'
        && $request['quality'] === 'high'
        && $request['metadata'] === ['generation_id' => (string) $model->id]);
});

// ... existing generate() tests ...

test('mock image generator returns 30 days of deterministic usage by default', function (): void {
    Carbon::setTestNow('2026-01-30 12:00:00');

    $generator = new MockImageGenerator;
    $usage = $generator->usage();

    expect($usage)->toBeInstanceOf(UsageData::class)
        ->and($usage->startDate)->toBe('2026-01-01')
        ->and($usage->endDate)->toBe('2026-01-30')
        ->and($usage->data)->toHaveCount(30)
        ->and($usage->data[0]->date)->toBe('2026-01-01')
        ->and($usage->data[29]->date)->toBe('2026-01-30')
        ->and($usage->total->date)->toBeNull();

    // Deterministic output for identical date ranges
    $secondCall = $generator->usage();
    expect($secondCall->total->costUsd)->toBe($usage->total->costUsd)
        ->and($secondCall->total->generations)->toBe($usage->total->generations);

    Carbon::setTestNow();
});

test('mock image generator aggregates succeeded in-memory generations into usage buckets', function (): void {
    $generator = new MockImageGenerator;

    $model = ImageGeneration::factory()->create([
        'prompt' => 'A cyberpunk street',
        'quality' => Quality::High,
        'size' => Size::Square,
    ]);

    // Trigger generation and poll find() so MockImageGenerator transitions it to Succeeded with usage data
    $queued = $generator->generate($model);
    $completed = $generator->find($queued->id);

    expect($completed->status)->toBe(Status::Succeeded->value);

    $today = Carbon::now('UTC')->format('Y-m-d');
    $usage = $generator->usage($today, $today);

    expect($usage->startDate)->toBe($today)
        ->and($usage->endDate)->toBe($today)
        ->and($usage->data)->toHaveCount(1)
        ->and($usage->data[0]->generations)->toBe(1)
        ->and($usage->total->generations)->toBe(1)
        ->and((float) $usage->total->costUsd)->toBeGreaterThan(0);
});

test('pixelforge image generator fetches usage with query parameters, headers, and caches response', function (): void {
    Cache::flush();

    $baseUrl = rtrim((string) config('ai-image.base_url'), '/');

    Http::fake([
        "{$baseUrl}/usage*" => Http::response([
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
            'data' => [
                [
                    'date' => '2026-01-01',
                    'generations' => 4,
                    'input_text_tokens' => 120,
                    'input_image_tokens' => 0,
                    'output_tokens' => 4400,
                    'reasoning_tokens' => 800,
                    'cost_usd' => '0.320000000',
                ],
            ],
            'total' => [
                'generations' => 4,
                'input_text_tokens' => 120,
                'input_image_tokens' => 0,
                'output_tokens' => 4400,
                'reasoning_tokens' => 800,
                'cost_usd' => '0.320000000',
            ],
        ], 200),
    ]);

    $generator = new PixelforgeImageGenerator(
        baseUrl: $baseUrl,
        apiKey: 'pf_test_secret',
    );

    $usage = $generator->usage('2026-01-01', '2026-01-31');

    expect($usage)->toBeInstanceOf(UsageData::class)
        ->and($usage->startDate)->toBe('2026-01-01')
        ->and($usage->endDate)->toBe('2026-01-31')
        ->and($usage->data)->toHaveCount(1)
        ->and($usage->data[0]->generations)->toBe(4)
        ->and($usage->total->costUsd)->toBe('0.320000000');

    // Second identical call should hit the 5-minute cache rather than making another HTTP request
    $cachedUsage = $generator->usage('2026-01-01', '2026-01-31');
    expect($cachedUsage->total->costUsd)->toBe('0.320000000');

    Http::assertSentCount(1);
    Http::assertSent(fn ($request): bool => str_starts_with($request->url(), "{$baseUrl}/usage")
        && $request->hasHeader('Authorization', 'Bearer pf_test_secret')
        && $request['start_date'] === '2026-01-01'
        && $request['end_date'] === '2026-01-31');
});

test('pixelforge image generator omits null date query parameters and throws on error response', function (): void {
    Cache::flush();

    $baseUrl = rtrim((string) config('ai-image.base_url'), '/');

    Http::fake([
        "{$baseUrl}/usage*" => Http::response([
            'error' => ['message' => 'Invalid API key'],
        ], 401),
    ]);

    $generator = new PixelforgeImageGenerator(
        baseUrl: $baseUrl,
        apiKey: 'pf_invalid_key',
    );

    expect(fn (): UsageData => $generator->usage())
        ->toThrow(RequestException::class);

    Http::assertSent(fn ($request): bool => $request->url() === "{$baseUrl}/usage"
        && $request->data() === []);
});


