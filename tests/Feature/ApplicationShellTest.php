<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot visit authenticated application destinations', function (string $routeName) {
    $this->get(route($routeName))->assertRedirect(route('login'));
})->with([
    'learning paths' => 'learn',
    'labs' => 'labs',
    'progress' => 'progress',
    'achievements' => 'achievements',
    'profile' => 'profile.show',
]);

test('verified users can visit authenticated application destinations', function (
    string $routeName,
    string $component,
) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route($routeName))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component($component));
})->with([
    'learning paths' => ['learn', 'learning-paths'],
    'labs' => ['labs', 'labs'],
    'progress' => ['progress', 'progress'],
    'achievements' => ['achievements', 'achievements'],
    'profile' => ['profile.show', 'profile'],
]);
