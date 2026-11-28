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

test('renders paginated credit purchase history ordered newest first for the authenticated user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    // Create 12 purchases for the authenticated user with distinct timestamps
    $purchases = collect(range(1, 12))->map(function (int $i) use ($user) {
        return CreditPurchase::factory()->create([
            'user_id' => $user->id,
            'reference_id' => Str::uuid()->toString(),
            'amount' => $i * 10,
            'created_at' => now()->subMinutes(12 - $i),
        ]);
    });

    // Create a purchase for another user to verify isolation
    CreditPurchase::factory()->create([
        'user_id' => $otherUser->id,
        'reference_id' => Str::uuid()->toString(),
        'amount' => 999,
    ]);

    $newestPurchase = $purchases->last();

    // Page 1: should contain the 10 most recent purchases
    actingAs($user)
        ->get(route('credits.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('credits/Index')
            ->has('purchases.data', 10)
            ->where('purchases.data.0.id', $newestPurchase->id)
            ->where('purchases.data.0.reference_id', $newestPurchase->reference_id)
            ->where('purchases.data.0.amount', 120)
            ->where('purchases.data.0.created_at', $newestPurchase->created_at->toIso8601String())
            ->where('purchases.meta.current_page', 1)
            ->where('purchases.meta.last_page', 2)
            ->where('purchases.meta.per_page', 10)
            ->where('purchases.meta.total', 12)
        );

    // Page 2: should contain the remaining 2 oldest purchases
    actingAs($user)
        ->get(route('credits.index', ['page' => 2]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('credits/Index')
            ->has('purchases.data', 2)
            ->where('purchases.meta.current_page', 2)
        );
});
