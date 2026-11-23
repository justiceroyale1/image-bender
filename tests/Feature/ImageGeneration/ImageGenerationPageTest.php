<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('can access new image generation page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('generations.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('generations/New'));
});

test('can access image generation view page')->todo();
