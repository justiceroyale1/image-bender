<?php

use App\Contracts\ImageGenerator;
use App\Models\User;
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

test('renders the usage page with 30 days of data by default', function () {
    Carbon::setTestNow('2026-01-30 12:00:00');

    test()->get(route('usage.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('usage/Index')
            ->where('filters', [])
            ->loadDeferredProps(fn (Assert $reload) => $reload
                ->where('deferred.error', null)
                ->where('deferred.usage.start_date', '2026-01-01')
                ->where('deferred.usage.end_date', '2026-01-30')
                ->has('deferred.usage.data', 30)
                ->has('deferred.usage.total', fn (Assert $total) => $total
                    ->hasAll([
                        'generations',
                        'input_text_tokens',
                        'input_image_tokens',
                        'output_tokens',
                        'reasoning_tokens',
                        'billed_usd',
                    ])
                    ->missing('date')
                )
            )
        );
});

test('renders the usage page with date filters and applies 1.25x markup', function () {
    $expectedMarkedUpTotal = app(ImageGenerator::class)
        ->usage('2026-01-01', '2026-01-31')
        ->withMarkup()
        ->total
        ->costUsd;

    test()->get(route('usage.index', [
        'start_date' => '2026-01-01',
        'end_date' => '2026-01-31',
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('usage/Index')
            ->where('filters', [
                'start_date' => '2026-01-01',
                'end_date' => '2026-01-31',
            ])
            ->loadDeferredProps(fn (Assert $reload) => $reload                
                ->where('deferred.error', null)
                ->where('deferred.usage.start_date', '2026-01-01')
                ->where('deferred.usage.end_date', '2026-01-31')
                ->has('deferred.usage.data', 31)
                ->where('deferred.usage.data.0.date', '2026-01-01')
                ->where('deferred.usage.data.30.date', '2026-01-31')
                ->where('deferred.usage.total.billed_usd', $expectedMarkedUpTotal)
            )
        );
});

test('handles upstream api request exceptions gracefully', function () {
    // Switch to the real HTTP-backed driver and fake the HTTP layer
    config(['ai-image.driver' => 'pixelforge']);

    Http::fake([
        '*/usage*' => Http::response([
            'error' => ['message' => 'Upstream quota exceeded.'],
        ], 502),
    ]);

    test()->get(route('usage.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('usage/Index')
            ->loadDeferredProps(fn (Assert $reload) => $reload                
                ->missing('deferred.usage')
                ->where('deferred.error', 'Upstream quota exceeded.')
            )
        );
});

test('falls back to a generic error message when request exception lacks an error message', function () {
    config(['ai-image.driver' => 'pixelforge']);

    Http::fake([
        '*/usage*' => Http::response([], 500),
    ]);

    test()->get(route('usage.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('usage/Index')
            ->loadDeferredProps(fn (Assert $reload) => $reload                
                ->missing('deferred.usage')
                ->where('deferred.error', 'Unable to fetch usage data.')
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

//     test()->get(route('usage.index'))
//         ->assertOk()
//         ->assertInertia(fn (Assert $page) => $page
//             ->component('usage/Index')
//             ->loadDeferredProps(fn (Assert $reload) => $reload                
//                 ->missing('deferred.usage')
//                 ->where('deferred.error', 'Unable to fetch usage data.')
//             )
//         );
// });

describe('validation', function () {
    test('rejects invalid date formats', function () {
        test()->get(route('usage.index', [
            'start_date' => '01-01-2026',
            'end_date' => 'not-a-date',
        ]))->assertSessionHasErrors(['start_date', 'end_date']);
    });

    test('rejects an end date before the start date', function () {
        test()->get(route('usage.index', [
            'start_date' => '2026-02-10',
            'end_date' => '2026-02-05',
        ]))->assertSessionHasErrors(['end_date']);
    });

    test('rejects date ranges exceeding 92 days', function () {
        test()->get(route('usage.index', [
            'start_date' => '2026-01-01',
            'end_date' => '2026-04-03',
        ]))->assertSessionHasErrors([
            'end_date' => 'The date range cannot exceed 92 days.',
        ]);
    });

    test('allows date ranges of exactly 92 days', function () {
        test()->get(route('usage.index', [
            'start_date' => '2026-01-01',
            'end_date' => '2026-04-02',
        ]))
            ->assertSessionHasNoErrors()
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('usage/Index')
                ->loadDeferredProps(fn (Assert $reload) => $reload
                    ->has('deferred.usage.data', 92)
                )
            );
    });
});
