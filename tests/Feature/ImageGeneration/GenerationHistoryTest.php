<?php

use App\Models\ImageGeneration;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;
use Inertia\Testing\AssertableInertia as Assert;

test('passes paginated user generation history in descending order', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $older = ImageGeneration::factory()->for($user)->create([
        'prompt' => 'First prompt',
        'status' => 'succeeded',
        'created_at' => now()->subHour(),
    ]);

    $newer = ImageGeneration::factory()->for($user)->create([
        'prompt' => 'Second prompt',
        'status' => 'queued',
        'created_at' => now(),
    ]);

    // Should be excluded
    ImageGeneration::factory()->for($otherUser)->create(['prompt' => 'Secret prompt']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page): AssertableInertia => $page
            ->has('generationHistory.data', 2)
            ->where('generationHistory.data.0.id', $newer->id)
            ->where('generationHistory.data.0.status', 'queued')
            ->where('generationHistory.data.1.id', $older->id)
            ->where('generationHistory.data.1.status', 'succeeded')
        );
});

test('includes generation history on every authenticated inertia route', function (): void {
    $user = User::factory()->create();
    $generation = ImageGeneration::factory()->for($user)->create([
        'prompt' => 'Cyberpunk street market',
        'status' => 'succeeded',
    ]);

    // Collect all GET routes protected by 'auth' that don't require URL parameters
    // Exclude routes that start with 'settings' or 'api/' as they are not relevant for this test
    $routes = collect(Route::getRoutes()->getRoutesByMethod()['GET'] ?? [])
        ->filter(function ($route): bool {
            $middlewares = $route->gatherMiddleware();
            $exemptedPrefixes = ['settings', 'api/'];

            return in_array('auth', $middlewares)
                && ! str_contains($route->uri(), '{')
                && ! str_starts_with($route->uri(), '_')
                && ! collect($exemptedPrefixes)
                    ->contains(fn ($prefix): bool => str_starts_with($route->uri(), $prefix));
        })
        ->map(fn ($route): string => '/'.ltrim($route->uri(), '/'))
        ->unique();

    expect($routes)->not->toBeEmpty();

    foreach ($routes as $uri) {
        $response = $this->actingAs($user)
            ->get($uri);

            if($response->getStatusCode() === 302){
                continue; // Skip routes that redirect (e.g., logout, etc.)
            }else{
                $response->assertOk();
            }

            $response->assertInertia(fn (Assert $page): AssertableInertia => $page
                ->has('generationHistory.data', 1)
                ->where('generationHistory.data.0.id', $generation->id)
                ->where('generationHistory.data.0.status', 'succeeded')
            );
    }
});
