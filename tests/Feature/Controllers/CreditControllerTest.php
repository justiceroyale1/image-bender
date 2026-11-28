<?php

use App\Models\CreditPurchase;
use App\Models\CreditWallet;
use App\Models\User;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

describe('index', function () {
    test('guests are redirected to login', function () {
        get(route('credits.index'))
            ->assertRedirect(route('login'));
    });

    test('renders the credits index page with 0 available credits when user has no wallet', function () {
        $user = User::factory()->create();

        actingAs($user)
            ->get(route('credits.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('credits/Index')
                ->where('availableCredits', 0)
            );
    });

    test('renders the credits index page with the users current wallet balance', function () {
        $user = User::factory()->create();
        CreditWallet::factory()->create([
            'user_id' => $user->id,
            'balance' => 250.5,
        ]);

        actingAs($user)
            ->get(route('credits.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('credits/Index')
                ->where('availableCredits', 250.5)
            );
    });
});

describe('store', function () {
    test('guests are redirected to login', function () {
        post(route('credits.store'), [
            'amount' => 500,
            'reference_id' => Str::uuid()->toString(),
        ])->assertRedirect(route('login'));
    });

    test('creates a credit purchase and initializes the users wallet on first purchase', function () {
        $user = User::factory()->create();
        $referenceId = Str::uuid()->toString();

        actingAs($user)
            ->post(route('credits.store'), [
                'amount' => 500,
                'reference_id' => $referenceId,
            ])
            ->assertRedirect(route('credits.index'))
            ->assertSessionHasNoErrors();

        assertDatabaseHas('credit_purchases', [
            'user_id' => $user->id,
            'reference_id' => $referenceId,
            'amount' => 500 * CreditPurchase::SCALE,
        ]);

        assertDatabaseHas('credit_wallets', [
            'user_id' => $user->id,
            'balance' => 500 * CreditWallet::SCALE,
        ]);
    });

    test('increments an existing wallet balance on subsequent purchases', function () {
        $user = User::factory()->create();
        CreditWallet::factory()->create([
            'user_id' => $user->id,
            'balance' => 200,
        ]);

        $referenceId = Str::uuid()->toString();

        actingAs($user)
            ->post(route('credits.store'), [
                'amount' => 500,
                'reference_id' => $referenceId,
            ])
            ->assertRedirect(route('credits.index'))
            ->assertSessionHasNoErrors();

        assertDatabaseHas('credit_wallets', [
            'user_id' => $user->id,
            'balance' => 700 * CreditWallet::SCALE,
        ]);
    });

    test('validates required and malformed inputs', function (array $payload, array $expectedErrors) {
        $user = User::factory()->create();

        actingAs($user)
            ->from(route('credits.index'))
            ->post(route('credits.store'), $payload)
            ->assertRedirect(route('credits.index'))
            ->assertSessionHasErrors($expectedErrors);

        assertDatabaseCount('credit_purchases', 0);
        assertDatabaseCount('credit_wallets', 0);
    })->with([
        'missing fields' => [
            [],
            ['amount', 'reference_id'],
        ],
        'amount below minimum of 5' => [
            ['amount' => 4, 'reference_id' => Str::uuid()->toString()],
            ['amount'],
        ],
        'decimal amount not allowed' => [
            ['amount' => 50.5, 'reference_id' => Str::uuid()->toString()],
            ['amount'],
        ],
        'invalid uuid reference_id' => [
            ['amount' => 50, 'reference_id' => 'not-a-valid-uuid'],
            ['reference_id'],
        ],
    ]);

    test('prevents purchasing credits beyond the 1,000,000 wallet limit', function () {
        $user = User::factory()->create();
        CreditWallet::create([
            'user_id' => $user->id,
            'balance' => 999_900,
        ]);

        actingAs($user)
            ->from(route('credits.index'))
            ->post(route('credits.store'), [
                'amount' => 101,
                'reference_id' => Str::uuid()->toString(),
            ])
            ->assertRedirect(route('credits.index'))
            ->assertSessionHasErrors(['amount']);

        assertDatabaseCount('credit_purchases', 0);
    });

    test('returns an reference_id validation error when DuplicateCreditPurchased is thrown', function () {
        $user = User::factory()->create();
        $referenceId = Str::uuid()->toString();

        CreditPurchase::create([
            'user_id' => $user->id,
            'reference_id' => $referenceId,
            'amount' => 500,
        ]);

        actingAs($user)
            ->from(route('credits.index'))
            ->post(route('credits.store'), [
                'amount' => 500,
                'reference_id' => $referenceId,
            ])
            ->assertRedirect(route('credits.index'))
            ->assertSessionHasErrors(['reference_id']);

        assertDatabaseCount('credit_purchases', 1);
    });

    test('handles unexpected exceptions gracefully and flashes a general error', function () {
        $user = User::factory()->create();

        CreditWallet::creating(function () {
            throw new RuntimeException('Simulated database failure');
        });

        actingAs($user)
            ->from(route('credits.index'))
            ->post(route('credits.store'), [
                'amount' => 500,
                'reference_id' => Str::uuid()->toString(),
            ])
            ->assertRedirect(route('credits.index'))
            ->assertSessionHasErrors([
                'general' => 'An error occurred while processing your credit purchase. Please try again later.',
            ]);

        // Also verifies DB::transaction rolled back the CreditPurchase record
        assertDatabaseCount('credit_purchases', 0);
        assertDatabaseCount('credit_wallets', 0);

        CreditWallet::flushEventListeners();
    });
});
