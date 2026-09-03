<?php

use App\Models\Lab;
use App\Models\LabProgress;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from lab details', function () {
    $lab = Lab::factory()->create();

    $response = $this->get(route('labs.show', ['lab' => $lab->slug]));

    $response->assertRedirect(route('login'));
});

test('unverified users are redirected from lab details', function () {
    $user = User::factory()->unverified()->create();
    $lab = Lab::factory()->create();

    $response = $this->actingAs($user)->get(route('labs.show', ['lab' => $lab->slug]));

    $response->assertRedirect(route('verification.notice'));
});

test('verified users see canonical lab details without starting progress', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('labs.show', [
        'lab' => 'sql-injection-authentication-bypass',
    ]));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('lab-detail')
        ->where('lab', [
            'slug' => 'sql-injection-authentication-bypass',
            'title' => 'SQL Injection Authentication Bypass',
            'description' => 'Explore how unsafe query construction can turn untrusted login input into an authentication decision.',
            'vulnerability_type' => 'sql_injection',
            'difficulty' => 'easy',
            'scenario' => 'A simulated login form combines submitted credentials with an unsafe database lookup. The evaluator models an authentication bypass without executing SQL.',
            'state' => 'not_started',
        ])
        ->where('lesson', [
            'slug' => 'sql-injection-authentication-bypass',
            'title' => 'SQL Injection Authentication Bypass',
            'summary' => 'Understand how unsafe query construction can turn login input into an authentication bypass.',
            'module' => [
                'title' => 'Injection',
            ],
            'learning_path' => [
                'slug' => 'web-security-fundamentals',
                'title' => 'Web Security Fundamentals',
            ],
        ])
        ->missing('runtime')
        ->missing('payload')
        ->missing('lab.id')
        ->missing('lab.created_at')
        ->missing('lab.updated_at'),
    );
    $this->assertDatabaseCount('lab_progress', 0);
});

test('unknown lab slugs return not found', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('labs.show', [
        'lab' => 'missing-lab',
    ]));

    $response->assertNotFound();
});

test('lab states belong only to the current user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $lab = Lab::factory()->create();
    LabProgress::factory()->for($user)->for($lab)->started()->create();
    LabProgress::factory()->for($otherUser)->for($lab)->completed()->create();

    $response = $this->actingAs($user)->get(route('labs.show', ['lab' => $lab->slug]));

    $response->assertInertia(
        fn (Assert $page) => $page->where('lab.state', 'in_progress'),
    );

    $user->labProgress()->whereBelongsTo($lab, 'lab')->update([
        'completed_at' => now(),
    ]);

    $this->actingAs($user)->get(route('labs.show', ['lab' => $lab->slug]))->assertInertia(
        fn (Assert $page) => $page->where('lab.state', 'completed'),
    );
});

test('labs without related lessons render safely', function () {
    $user = User::factory()->create();
    $lab = Lab::factory()->create();

    $response = $this->actingAs($user)->get(route('labs.show', ['lab' => $lab->slug]));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('lab-detail')
        ->where('lab.slug', $lab->slug)
        ->where('lesson', null),
    );
});

test('viewing labs preserves existing progress timestamps', function () {
    $user = User::factory()->create();
    $startedLab = Lab::factory()->create();
    $completedLab = Lab::factory()->create();
    $startedProgress = LabProgress::factory()->for($user)->for($startedLab)->started()->create([
        'started_at' => '2026-09-02 09:00:00',
    ]);
    $completedProgress = LabProgress::factory()->for($user)->for($completedLab)->completed()->create([
        'started_at' => '2026-09-02 08:00:00',
        'completed_at' => '2026-09-02 08:30:00',
    ]);
    $this->travelTo('2026-09-02 12:00:00');

    $this->actingAs($user)->get(route('labs.show', ['lab' => $startedLab->slug]))->assertInertia(
        fn (Assert $page) => $page->where('lab.state', 'in_progress'),
    );
    $this->actingAs($user)->get(route('labs.show', ['lab' => $completedLab->slug]))->assertInertia(
        fn (Assert $page) => $page->where('lab.state', 'completed'),
    );

    $this->assertDatabaseCount('lab_progress', 2);
    expect($startedProgress->fresh()?->started_at->toDateTimeString())->toBe('2026-09-02 09:00:00')
        ->and($startedProgress->fresh()?->completed_at)->toBeNull()
        ->and($completedProgress->fresh()?->started_at->toDateTimeString())->toBe('2026-09-02 08:00:00')
        ->and($completedProgress->fresh()?->completed_at?->toDateTimeString())->toBe('2026-09-02 08:30:00');
});
