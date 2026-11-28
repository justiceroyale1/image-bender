<?php

use App\Contracts\ImageGenerator;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Cache::flush();
    config(['ai-image.driver' => 'mock']);
    test()->actingAs(User::factory()->create());
});

afterEach(function () {
    Carbon::setTestNow();
});

test('defers usageSummary on initial dashboard load', function () {
    test()->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->missing('usageSummary')
        );
});

test('resolves deferred usageSummary with 30 days of marked-up mock data', function () {
    Carbon::setTestNow('2026-01-30 12:00:00');

    // Compute exact expected values from MockImageGenerator + 1.25x markup
    $expectedUsage = app(ImageGenerator::class)->usage()->withMarkup();
    $expectedToday = $expectedUsage->data[array_key_last($expectedUsage->data)];
    $expectedTotalTokens = $expectedUsage->total->inputTextTokens
        + $expectedUsage->total->inputImageTokens
        + $expectedUsage->total->outputTokens
        + $expectedUsage->total->reasoningTokens;

    test()->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->missing('usageSummary')
            ->loadDeferredProps(fn (Assert $reload) => $reload
                ->where('usageSummary.error', null)
                ->where('usageSummary.period', [
                    'start_date' => '2026-01-01',
                    'end_date' => '2026-01-30',
                ])
                ->where('usageSummary.total_generations', $expectedUsage->total->generations)
                ->where('usageSummary.total_tokens', $expectedTotalTokens)
                ->where('usageSummary.total_billed_usd', $expectedUsage->total->costUsd)
                ->where('usageSummary.today_generations', $expectedToday->generations)
                ->where('usageSummary.today_billed_usd', $expectedToday->costUsd)
                ->has('usageSummary.recent_days', 7)
                ->where('usageSummary.recent_days.0.date', '2026-01-24')
                ->where('usageSummary.recent_days.6.date', '2026-01-30')
            )
        );
});

test('handles upstream api request exceptions inside deferred usageSummary', function () {
    config(['ai-image.driver' => 'pixelforge']);

    Http::fake([
        '*/usage*' => Http::response([
            'error' => ['message' => 'Upstream quota exceeded.'],
        ], 502),
    ]);

    test()->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->loadDeferredProps(fn (Assert $reload) => $reload
                ->where('usageSummary', [
                    'error' => 'Upstream quota exceeded.',
                ])
            )
        );
});

test('falls back to a generic error message when deferred request fails without message', function () {
    config(['ai-image.driver' => 'pixelforge']);

    Http::fake([
        '*/usage*' => Http::response([], 500),
    ]);

    test()->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->loadDeferredProps(fn (Assert $reload) => $reload
                ->where('usageSummary', [
                    'error' => 'Unable to fetch usage data.',
                ])
            )
        );
});


// test('displays connection error message when ConnectionException is thrown on deferred request', function () {
//     config(['ai-image.driver' => 'pixelforge']);

//     $message = 'cURL error 28: Resolving timed out after 10000 milliseconds (see https://curl.se/libcurl/c/libcurl-errors.html) for https://pixelforge.promptedit.dev/v1/usage';

//     Http::fake([
//         '*/usage*' => Http::response([
//             'error' => ['message' => $message],
//         ], 500),
//     ]);

//     test()->get(route('dashboard'))
//         ->assertOk()
//         ->assertInertia(fn (Assert $page) => $page
//             ->component('Dashboard')
//             ->loadDeferredProps(fn (Assert $reload) => $reload
//                 ->where('usageSummary', [
//                     'error' => $message,
//                 ])
//             )
//         );
// });
