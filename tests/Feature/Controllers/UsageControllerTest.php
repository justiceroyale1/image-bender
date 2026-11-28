<?php

use App\Contracts\ImageGenerator;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    Cache::flush();
    config(['ai-image.driver' => 'mock']);
    $this->actingAs(User::factory()->create());
});

afterEach(function (): void {
    Date::setTestNow();
});

test('renders the usage page with 30 days of data by default', function (): void {
    Date::setTestNow('2026-01-30 12:00:00');

    $this->get(route('usage.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableInertia => $page
            ->component('usage/Index')
            ->where('error', null)
            ->where('filters', [])
            ->where('usage.start_date', '2026-01-01')
            ->where('usage.end_date', '2026-01-30')
            ->has('usage.data', 30)
            ->has('usage.total', fn (Assert $total): AssertableInertia => $total
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
        );
});

test('renders the usage page with date filters and applies 1.25x markup', function (): void {
    $expectedMarkedUpTotal = resolve(ImageGenerator::class)
        ->usage('2026-01-01', '2026-01-31')
        ->withMarkup()
        ->total
        ->costUsd;

    $this->get(route('usage.index', [
        'start_date' => '2026-01-01',
        'end_date' => '2026-01-31',
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableInertia => $page
            ->component('usage/Index')
            ->where('error', null)
            ->where('filters', [
                'start_date' => '2026-01-01',
                'end_date' => '2026-01-31',
            ])
            ->where('usage.start_date', '2026-01-01')
            ->where('usage.end_date', '2026-01-31')
            ->has('usage.data', 31)
            ->where('usage.data.0.date', '2026-01-01')
            ->where('usage.data.30.date', '2026-01-31')
            ->where('usage.total.billed_usd', $expectedMarkedUpTotal)
        );
});

test('handles upstream api request exceptions gracefully', function (): void {
    // Switch to the real HTTP-backed driver and fake the HTTP layer
    config(['ai-image.driver' => 'pixelforge']);

    Http::fake([
        '*/usage*' => Http::response([
            'error' => ['message' => 'Upstream quota exceeded.'],
        ], 502),
    ]);

    $this->get(route('usage.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableInertia => $page
            ->component('usage/Index')
            ->where('usage', null)
            ->where('error', 'Upstream quota exceeded.')
        );
});

test('falls back to a generic error message when request exception lacks an error message', function (): void {
    config(['ai-image.driver' => 'pixelforge']);

    Http::fake([
        '*/usage*' => Http::response([], 500),
    ]);

    $this->get(route('usage.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableInertia => $page
            ->component('usage/Index')
            ->where('usage', null)
            ->where('error', 'Unable to fetch usage data.')
        );
});

describe('validation', function (): void {
    test('rejects invalid date formats', function (): void {
        $this->get(route('usage.index', [
            'start_date' => '01-01-2026',
            'end_date' => 'not-a-date',
        ]))->assertSessionHasErrors(['start_date', 'end_date']);
    });

    test('rejects an end date before the start date', function (): void {
        $this->get(route('usage.index', [
            'start_date' => '2026-02-10',
            'end_date' => '2026-02-05',
        ]))->assertSessionHasErrors(['end_date']);
    });

    test('rejects date ranges exceeding 92 days', function (): void {
        $this->get(route('usage.index', [
            'start_date' => '2026-01-01',
            'end_date' => '2026-04-03',
        ]))->assertSessionHasErrors([
            'end_date' => 'The date range cannot exceed 92 days.',
        ]);
    });

    test('allows date ranges of exactly 92 days', function (): void {
        $this->get(route('usage.index', [
            'start_date' => '2026-01-01',
            'end_date' => '2026-04-02',
        ]))
            ->assertSessionHasNoErrors()
            ->assertOk()
            ->assertInertia(fn (Assert $page): AssertableInertia => $page
                ->component('usage/Index')
                ->has('usage.data', 92)
            );
    });
});
