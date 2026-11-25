<?php

use App\Models\ImageGeneration;
use App\Models\User;
use Inertia\Testing\AssertableInertia;
use Inertia\Testing\AssertableInertia as Assert;

test('can access new image generation page', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('generations.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableInertia => $page->component('generations/New'));
});

test('can access image generation view page', function (): void {
    $user = User::factory()->create();

    $generation = ImageGeneration::factory()->create([
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('generations.show', ['generation' => $generation->ulid]))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableInertia => $page->component('generations/Show'));
});

test('can not view image generation belonging to other users', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $generation = ImageGeneration::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $this->actingAs($user)
        ->get(route('generations.show', ['generation' => $generation->ulid]))
        ->assertForbidden();
});
