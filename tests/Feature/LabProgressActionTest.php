<?php

use App\Models\Lab;
use App\Models\LabProgress;
use App\Models\User;
use App\Services\LearningProgressService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot mutate lab progress', function (string $routeName) {
    $lab = Lab::factory()->create();

    $response = $this->post(route($routeName, ['lab' => $lab->slug]));

    $response->assertRedirect(route('login'));
    $this->assertDatabaseCount('lab_progress', 0);
})->with([
    'start' => 'labs.start',
    'complete' => 'labs.complete',
]);

test('unverified users cannot mutate lab progress', function (string $routeName) {
    $user = User::factory()->unverified()->create();
    $lab = Lab::factory()->create();

    $response = $this->actingAs($user)->post(route($routeName, ['lab' => $lab->slug]));

    $response->assertRedirect(route('verification.notice'));
    $this->assertDatabaseCount('lab_progress', 0);
})->with([
    'start' => 'labs.start',
    'complete' => 'labs.complete',
]);

describe('starting labs', function () {
    test('verified users start only their own lab with a server timestamp', function () {
        $this->travelTo('2026-09-03 10:00:00');
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $lab = Lab::factory()->create();
        $otherProgress = LabProgress::factory()->for($otherUser)->for($lab)->completed()->create([
            'started_at' => '2026-09-03 08:00:00',
            'completed_at' => '2026-09-03 08:30:00',
        ]);

        $response = $this->actingAs($user)->post(route('labs.start', [
            'lab' => $lab->slug,
        ]), [
            'user_id' => $otherUser->id,
            'lab_id' => 999999,
            'started_at' => '2000-01-01 00:00:00',
            'completed_at' => '2000-01-01 00:00:00',
            'state' => 'completed',
        ]);

        $response->assertRedirect(route('labs.workspace', ['lab' => $lab->slug]));
        $this->assertDatabaseHas('lab_progress', [
            'user_id' => $user->id,
            'lab_id' => $lab->id,
            'started_at' => '2026-09-03 10:00:00',
            'completed_at' => null,
        ]);
        expect($user->labProgress()->whereBelongsTo($lab, 'lab')->count())->toBe(1)
            ->and((new LearningProgressService)->labState($user, $lab))->toBe('in_progress')
            ->and($otherProgress->fresh()?->started_at->toDateTimeString())->toBe('2026-09-03 08:00:00')
            ->and($otherProgress->fresh()?->completed_at?->toDateTimeString())->toBe('2026-09-03 08:30:00');
    });

    test('starting twice preserves the original start timestamp', function () {
        $user = User::factory()->create();
        $lab = Lab::factory()->create();
        $route = route('labs.start', ['lab' => $lab->slug]);
        $this->travelTo('2026-09-03 10:00:00');
        $this->actingAs($user)->post($route)->assertRedirect();
        $progress = $user->labProgress()->whereBelongsTo($lab, 'lab')->firstOrFail();

        $this->travelTo('2026-09-03 11:00:00');
        $response = $this->actingAs($user)->post($route);

        $response->assertRedirect();
        expect($user->labProgress()->whereBelongsTo($lab, 'lab')->count())->toBe(1)
            ->and($progress->fresh()?->started_at->toDateTimeString())->toBe('2026-09-03 10:00:00')
            ->and($progress->fresh()?->completed_at)->toBeNull();
    });

    test('starting a completed lab does not regress its timestamps', function () {
        $user = User::factory()->create();
        $lab = Lab::factory()->create();
        $progress = LabProgress::factory()->for($user)->for($lab)->completed()->create([
            'started_at' => '2026-09-03 09:00:00',
            'completed_at' => '2026-09-03 09:30:00',
        ]);
        $this->travelTo('2026-09-03 12:00:00');

        $response = $this->actingAs($user)->post(route('labs.start', ['lab' => $lab->slug]));

        $response->assertRedirect(route('labs.workspace', ['lab' => $lab->slug]));
        expect($user->labProgress()->whereBelongsTo($lab, 'lab')->count())->toBe(1)
            ->and($progress->fresh()?->started_at->toDateTimeString())->toBe('2026-09-03 09:00:00')
            ->and($progress->fresh()?->completed_at?->toDateTimeString())->toBe('2026-09-03 09:30:00');
    });
});

describe('completing labs', function () {
    test('verified users complete a fresh lab with server timestamps for only themselves', function () {
        $this->travelTo('2026-09-03 10:00:00');
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $lab = Lab::factory()->create();
        $otherProgress = LabProgress::factory()->for($otherUser)->for($lab)->started()->create([
            'started_at' => '2026-09-03 08:00:00',
        ]);

        $response = $this->actingAs($user)->post(route('labs.complete', [
            'lab' => $lab->slug,
        ]), [
            'user_id' => $otherUser->id,
            'lab_id' => 999999,
            'started_at' => '2000-01-01 00:00:00',
            'completed_at' => '2000-01-01 00:00:00',
            'state' => 'not_started',
        ]);

        $response->assertRedirect(route('labs.workspace', ['lab' => $lab->slug]));
        $this->assertDatabaseHas('lab_progress', [
            'user_id' => $user->id,
            'lab_id' => $lab->id,
            'started_at' => '2026-09-03 10:00:00',
            'completed_at' => '2026-09-03 10:00:00',
        ]);
        expect($user->labProgress()->whereBelongsTo($lab, 'lab')->count())->toBe(1)
            ->and((new LearningProgressService)->labState($user, $lab))->toBe('completed')
            ->and($otherProgress->fresh()?->started_at->toDateTimeString())->toBe('2026-09-03 08:00:00')
            ->and($otherProgress->fresh()?->completed_at)->toBeNull();
    });

    test('completing a started lab twice preserves both original timestamps', function () {
        $user = User::factory()->create();
        $lab = Lab::factory()->create();
        $progress = LabProgress::factory()->for($user)->for($lab)->started()->create([
            'started_at' => '2026-09-03 09:00:00',
        ]);
        $route = route('labs.complete', ['lab' => $lab->slug]);
        $this->travelTo('2026-09-03 10:00:00');
        $this->actingAs($user)->post($route)->assertRedirect();

        $this->travelTo('2026-09-03 11:00:00');
        $response = $this->actingAs($user)->post($route);

        $response->assertRedirect();
        expect($user->labProgress()->whereBelongsTo($lab, 'lab')->count())->toBe(1)
            ->and($progress->fresh()?->started_at->toDateTimeString())->toBe('2026-09-03 09:00:00')
            ->and($progress->fresh()?->completed_at?->toDateTimeString())->toBe('2026-09-03 10:00:00');
    });
});

test('lab progress routes return not found for unknown lab slugs', function (string $routeName) {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route($routeName, ['lab' => 'missing-lab']));

    $response->assertNotFound();
    $this->assertDatabaseCount('lab_progress', 0);
})->with([
    'start' => 'labs.start',
    'complete' => 'labs.complete',
]);

test('starting a canonical lab updates catalog detail and workspace state', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::factory()->create();
    $lab = Lab::query()->where('slug', 'idor')->firstOrFail();

    $this->actingAs($user)->post(route('labs.start', ['lab' => $lab->slug]))
        ->assertRedirect(route('labs.workspace', ['lab' => $lab->slug]));

    $this->get(route('labs'))->assertInertia(fn (Assert $page) => $page
        ->where('summary', [
            'total' => 5,
            'started' => 1,
            'in_progress' => 1,
            'completed' => 0,
            'remaining' => 5,
        ])
        ->where(
            'labs',
            fn (Collection $labs): bool => $labs->pluck('state', 'slug')->get($lab->slug) === 'in_progress',
        ),
    );
    $this->get(route('labs.show', ['lab' => $lab->slug]))->assertInertia(
        fn (Assert $page) => $page->where('lab.state', 'in_progress'),
    );
    $this->get(route('labs.workspace', ['lab' => $lab->slug]))->assertInertia(
        fn (Assert $page) => $page->where('lab.state', 'in_progress'),
    );
});

test('completing a canonical lab updates aggregates while another user remains fresh', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $lab = Lab::query()->where('slug', 'idor')->firstOrFail();
    LabProgress::factory()->for($user)->for($lab)->started()->create();

    $this->actingAs($user)->post(route('labs.complete', ['lab' => $lab->slug]))
        ->assertRedirect(route('labs.workspace', ['lab' => $lab->slug]));

    $this->get(route('labs'))->assertInertia(fn (Assert $page) => $page
        ->where('summary', [
            'total' => 5,
            'started' => 1,
            'in_progress' => 0,
            'completed' => 1,
            'remaining' => 4,
        ])
        ->where(
            'labs',
            fn (Collection $labs): bool => $labs->pluck('state', 'slug')->get($lab->slug) === 'completed',
        ),
    );
    $this->get(route('labs.show', ['lab' => $lab->slug]))->assertInertia(
        fn (Assert $page) => $page->where('lab.state', 'completed'),
    );
    $this->get(route('labs.workspace', ['lab' => $lab->slug]))->assertInertia(
        fn (Assert $page) => $page->where('lab.state', 'completed'),
    );
    $this->actingAs($otherUser)->get(route('labs'))->assertInertia(fn (Assert $page) => $page
        ->where('summary', [
            'total' => 5,
            'started' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'remaining' => 5,
        ])
        ->where(
            'labs',
            fn (Collection $labs): bool => $labs->pluck('state', 'slug')->get($lab->slug) === 'not_started',
        ),
    );
});

test('catalog detail workspace simulation and analysis remain read only', function () {
    $user = User::factory()->create();
    $lab = Lab::factory()->create(['slug' => 'reflected-xss']);
    $workspace = route('labs.workspace', ['lab' => $lab->slug]);

    $this->actingAs($user)->get(route('labs'))->assertOk();
    $this->get(route('labs.show', ['lab' => $lab->slug]))->assertOk();
    $this->get($workspace)->assertOk();
    $this->post(route('labs.simulate', ['lab' => $lab->slug]), [
        'input' => ['query' => '<script>inert()</script>'],
    ])->assertRedirect($workspace);
    $this->get($workspace)->assertInertia(fn (Assert $page) => $page
        ->where('lab.state', 'not_started')
        ->where('simulation.result.outcome', 'vulnerable_behavior')
        ->has('simulation.guidance.security_control'),
    );

    $this->assertDatabaseCount('lab_progress', 0);
});

test('simulating a completed lab preserves progress timestamps and state', function () {
    $user = User::factory()->create();
    $lab = Lab::factory()->create(['slug' => 'stored-xss']);
    $progress = LabProgress::factory()->for($user)->for($lab)->completed()->create([
        'started_at' => '2026-09-03 08:00:00',
        'completed_at' => '2026-09-03 08:30:00',
    ]);
    $workspace = route('labs.workspace', ['lab' => $lab->slug]);
    $this->travelTo('2026-09-03 12:00:00');

    $this->actingAs($user)->post(route('labs.simulate', ['lab' => $lab->slug]), [
        'input' => ['comment' => '<script>inert()</script>'],
    ])->assertRedirect($workspace);

    $this->get($workspace)->assertInertia(fn (Assert $page) => $page
        ->where('lab.state', 'completed')
        ->where('simulation.result.outcome', 'vulnerable_behavior'),
    );
    expect($progress->fresh()?->started_at->toDateTimeString())->toBe('2026-09-03 08:00:00')
        ->and($progress->fresh()?->completed_at?->toDateTimeString())->toBe('2026-09-03 08:30:00');
});
