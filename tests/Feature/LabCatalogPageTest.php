<?php

use App\Models\Lab;
use App\Models\LabProgress;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from the lab catalog', function () {
    $response = $this->get(route('labs'));

    $response->assertRedirect(route('login'));
});

test('unverified users are redirected from the lab catalog', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get(route('labs'));

    $response->assertRedirect(route('verification.notice'));
});

test('verified users see canonical lab metadata and learning context', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('labs'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('labs')
        ->where('summary', [
            'total' => 5,
            'started' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'remaining' => 5,
        ])
        ->has('labs', 5)
        ->where(
            'labs',
            fn (Collection $labs): bool => $labs->pluck('slug')->all() === [
                'idor',
                'reflected-xss',
                'sql-injection-authentication-bypass',
                'stored-xss',
                'weak-password-reset',
            ]
                && $labs->pluck('difficulty', 'slug')->all() === [
                    'idor' => 'easy',
                    'reflected-xss' => 'easy',
                    'sql-injection-authentication-bypass' => 'easy',
                    'stored-xss' => 'medium',
                    'weak-password-reset' => 'medium',
                ]
                && $labs->pluck('vulnerability_type', 'slug')->all() === [
                    'idor' => 'access_control',
                    'reflected-xss' => 'cross_site_scripting',
                    'sql-injection-authentication-bypass' => 'sql_injection',
                    'stored-xss' => 'cross_site_scripting',
                    'weak-password-reset' => 'authentication',
                ]
                && $labs->every(fn (array $lab): bool => $lab['state'] === 'not_started'),
        )
        ->where('labs.2', [
            'slug' => 'sql-injection-authentication-bypass',
            'title' => 'SQL Injection Authentication Bypass',
            'description' => 'Explore how unsafe query construction can turn untrusted login input into an authentication decision.',
            'vulnerability_type' => 'sql_injection',
            'difficulty' => 'easy',
            'state' => 'not_started',
            'lesson' => [
                'slug' => 'sql-injection-authentication-bypass',
                'title' => 'SQL Injection Authentication Bypass',
                'module' => [
                    'title' => 'Injection',
                ],
                'learning_path' => [
                    'slug' => 'web-security-fundamentals',
                    'title' => 'Web Security Fundamentals',
                ],
            ],
        ])
        ->missing('labs.2.scenario'),
    );
});

test('catalog progress and states belong only to the current user', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $startedLab = Lab::query()->where('slug', 'sql-injection-authentication-bypass')->firstOrFail();
    $completedLab = Lab::query()->where('slug', 'stored-xss')->firstOrFail();
    $otherUserLab = Lab::query()->where('slug', 'idor')->firstOrFail();
    LabProgress::factory()->for($user)->for($startedLab)->started()->create();
    LabProgress::factory()->for($user)->for($completedLab)->completed()->create();
    LabProgress::factory()->for($otherUser)->for($otherUserLab)->completed()->create();

    $response = $this->actingAs($user)->get(route('labs'));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('summary', [
            'total' => 5,
            'started' => 2,
            'in_progress' => 1,
            'completed' => 1,
            'remaining' => 4,
        ])
        ->where(
            'labs',
            fn (Collection $labs): bool => $labs->pluck('state', 'slug')->all() === [
                'idor' => 'not_started',
                'reflected-xss' => 'not_started',
                'sql-injection-authentication-bypass' => 'in_progress',
                'stored-xss' => 'completed',
                'weak-password-reset' => 'not_started',
            ],
        ),
    );
});

test('labs without associated lessons render safely', function () {
    $user = User::factory()->create();
    $lab = Lab::factory()->create([
        'slug' => 'catalog-only-lab',
        'title' => 'Catalog-only Lab',
    ]);

    $response = $this->actingAs($user)->get(route('labs'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('summary', [
            'total' => 1,
            'started' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'remaining' => 1,
        ])
        ->has('labs', 1)
        ->where('labs.0.slug', $lab->slug)
        ->where('labs.0.state', 'not_started')
        ->where('labs.0.lesson', null),
    );
});

test('empty lab catalogs render safely', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('labs'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('labs')
        ->where('summary', [
            'total' => 0,
            'started' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'remaining' => 0,
        ])
        ->has('labs', 0),
    );
});

test('catalog ordering and progress mapping do not use database ids', function () {
    $user = User::factory()->create();
    $laterAlphabeticalLab = Lab::factory()->create([
        'slug' => 'zulu-lab',
        'title' => 'Zulu Lab',
    ]);
    $earlierAlphabeticalLab = Lab::factory()->create([
        'slug' => 'alpha-lab',
        'title' => 'Alpha Lab',
    ]);
    LabProgress::factory()->for($user)->for($laterAlphabeticalLab)->completed()->create();

    expect($laterAlphabeticalLab->id)->toBeLessThan($earlierAlphabeticalLab->id);

    $response = $this->actingAs($user)->get(route('labs'));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('summary', [
            'total' => 2,
            'started' => 1,
            'in_progress' => 0,
            'completed' => 1,
            'remaining' => 1,
        ])
        ->where(
            'labs',
            fn (Collection $labs): bool => $labs->pluck('slug')->all() === [
                'alpha-lab',
                'zulu-lab',
            ] && $labs->pluck('state', 'slug')->all() === [
                'alpha-lab' => 'not_started',
                'zulu-lab' => 'completed',
            ],
        ),
    );
});
