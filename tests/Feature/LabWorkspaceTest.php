<?php

use App\Models\Lab;
use App\Models\LabProgress;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot open or submit a lab workspace', function () {
    $lab = Lab::factory()->create([
        'slug' => 'reflected-xss',
    ]);

    $this->get(route('labs.workspace', ['lab' => $lab->slug]))
        ->assertRedirect(route('login'));
    $this->post(route('labs.simulate', ['lab' => $lab->slug]), [
        'input' => ['query' => 'security basics'],
    ])->assertRedirect(route('login'));
});

test('unverified users cannot open or submit a lab workspace', function () {
    $user = User::factory()->unverified()->create();
    $lab = Lab::factory()->create([
        'slug' => 'reflected-xss',
    ]);

    $this->actingAs($user)
        ->get(route('labs.workspace', ['lab' => $lab->slug]))
        ->assertRedirect(route('verification.notice'));
    $this->actingAs($user)
        ->post(route('labs.simulate', ['lab' => $lab->slug]), [
            'input' => ['query' => 'security basics'],
        ])
        ->assertRedirect(route('verification.notice'));
});

test('verified users see the canonical safe workspace without starting progress', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('labs.workspace', [
        'lab' => 'idor',
    ]));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('lab-workspace')
        ->where('lab', [
            'slug' => 'idor',
            'title' => 'IDOR',
            'description' => 'Learn how missing object-level authorization can expose another user\'s data through a predictable identifier.',
            'vulnerability_type' => 'access_control',
            'difficulty' => 'easy',
            'state' => 'not_started',
        ])
        ->where('simulation.supported', true)
        ->where('simulation.instructions', 'The synthetic learner owns resource 1001. Request resource 2002 to inspect a fictional endpoint with no ownership check.')
        ->where('simulation.fields', [[
            'key' => 'resource_id',
            'label' => 'Synthetic resource identifier',
            'type' => 'text',
            'placeholder' => '1001',
            'max_length' => 16,
        ]])
        ->where('simulation.result', null)
        ->missing('lab.scenario')
        ->missing('simulation.class')
        ->missing('simulation.runtime'),
    );
    $this->assertDatabaseCount('lab_progress', 0);
});

test('unknown lab slugs return not found for workspace and simulation routes', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('labs.workspace', ['lab' => 'missing-lab']))
        ->assertNotFound();
    $this->actingAs($user)
        ->post(route('labs.simulate', ['lab' => 'missing-lab']))
        ->assertNotFound();
});

test('workspace GET preserves existing lab progress timestamps', function () {
    $user = User::factory()->create();
    $startedLab = Lab::factory()->create([
        'slug' => 'reflected-xss',
    ]);
    $completedLab = Lab::factory()->create([
        'slug' => 'stored-xss',
    ]);
    $startedProgress = LabProgress::factory()->for($user)->for($startedLab)->started()->create([
        'started_at' => '2026-09-03 09:00:00',
    ]);
    $completedProgress = LabProgress::factory()->for($user)->for($completedLab)->completed()->create([
        'started_at' => '2026-09-03 08:00:00',
        'completed_at' => '2026-09-03 08:30:00',
    ]);
    $this->travelTo('2026-09-03 12:00:00');

    $this->actingAs($user)
        ->get(route('labs.workspace', ['lab' => $startedLab->slug]))
        ->assertInertia(fn (Assert $page) => $page->where('lab.state', 'in_progress'));
    $this->actingAs($user)
        ->get(route('labs.workspace', ['lab' => $completedLab->slug]))
        ->assertInertia(fn (Assert $page) => $page->where('lab.state', 'completed'));

    expect($startedProgress->fresh()?->started_at->toDateTimeString())->toBe('2026-09-03 09:00:00')
        ->and($startedProgress->fresh()?->completed_at)->toBeNull()
        ->and($completedProgress->fresh()?->started_at->toDateTimeString())->toBe('2026-09-03 08:00:00')
        ->and($completedProgress->fresh()?->completed_at?->toDateTimeString())->toBe('2026-09-03 08:30:00');
});

test('valid simulation returns an ephemeral deterministic result without progress', function () {
    $user = User::factory()->create();
    $lab = Lab::factory()->create([
        'slug' => 'reflected-xss',
    ]);
    $workspace = route('labs.workspace', ['lab' => $lab->slug]);
    $input = '<script>globalThis.compromised = true</script>';

    $this->actingAs($user)
        ->from($workspace)
        ->post(route('labs.simulate', ['lab' => $lab->slug]), [
            'input' => ['query' => $input],
        ])
        ->assertRedirect($workspace);

    $this->get($workspace)->assertInertia(fn (Assert $page) => $page
        ->where('simulation.result.outcome', 'vulnerable_behavior')
        ->where('simulation.result.input.query', $input)
        ->has('simulation.result.steps', 4)
        ->where('simulation.result.steps.0.type', 'input')
        ->where('simulation.result.steps.1.type', 'application')
        ->where('simulation.result.steps.2.type', 'decision')
        ->where('simulation.result.steps.3.type', 'output'),
    );
    $this->get($workspace)->assertInertia(
        fn (Assert $page) => $page->where('simulation.result', null),
    );
    $this->assertDatabaseCount('lab_progress', 0);
});

test('normal and rejected outcomes reach the workspace as structured inert data', function (
    string $slug,
    array $input,
    string $inputKey,
    string $outcome,
) {
    $user = User::factory()->create();
    $lab = Lab::factory()->create([
        'slug' => $slug,
    ]);
    $workspace = route('labs.workspace', ['lab' => $lab->slug]);

    $this->actingAs($user)
        ->from($workspace)
        ->post(route('labs.simulate', ['lab' => $lab->slug]), [
            'input' => $input,
        ])
        ->assertRedirect($workspace);

    $this->get($workspace)->assertInertia(fn (Assert $page) => $page
        ->where('simulation.result.outcome', $outcome)
        ->where("simulation.result.input.{$inputKey}", $input[$inputKey])
        ->has('simulation.result.steps', 4)
        ->where('simulation.result.steps.0.type', 'input')
        ->where('simulation.result.steps.1.type', 'application')
        ->where('simulation.result.steps.2.type', 'decision')
        ->where('simulation.result.steps.3.type', 'output'),
    );
    $this->assertDatabaseCount('lab_progress', 0);
})->with([
    'normal behavior' => [
        'reflected-xss',
        ['query' => 'security basics'],
        'query',
        'normal_behavior',
    ],
    'rejected request' => [
        'idor',
        ['resource_id' => '9999'],
        'resource_id',
        'rejected',
    ],
]);

test('simulation validates field types and conservative input limits', function () {
    $user = User::factory()->create();
    $lab = Lab::factory()->create([
        'slug' => 'reflected-xss',
    ]);
    $workspace = route('labs.workspace', ['lab' => $lab->slug]);
    $simulation = route('labs.simulate', ['lab' => $lab->slug]);

    $this->actingAs($user)
        ->from($workspace)
        ->post($simulation, ['input' => ['query' => ['nested']]])
        ->assertRedirect($workspace)
        ->assertSessionHasErrors('input.query');

    $this->actingAs($user)
        ->from($workspace)
        ->post($simulation, ['input' => ['query' => str_repeat('a', 257)]])
        ->assertRedirect($workspace)
        ->assertSessionHasErrors('input.query');

    $this->actingAs($user)
        ->from($workspace)
        ->post($simulation, ['input' => ['query' => str_repeat('a', 256)]])
        ->assertRedirect($workspace)
        ->assertSessionDoesntHaveErrors();
});

test('learner input cannot select a simulator implementation', function () {
    $user = User::factory()->create();
    $lab = Lab::factory()->create([
        'slug' => 'idor',
    ]);
    $workspace = route('labs.workspace', ['lab' => $lab->slug]);

    $this->actingAs($user)
        ->from($workspace)
        ->post(route('labs.simulate', ['lab' => $lab->slug]), [
            'simulator' => 'App\\Arbitrary\\LearnerSelectedClass',
            'input' => ['resource_id' => '2002'],
        ])
        ->assertRedirect($workspace)
        ->assertSessionHasErrors('simulator');

    $this->assertDatabaseCount('lab_progress', 0);
});

test('simulation POST preserves progress for both current and other users', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $lab = Lab::factory()->create([
        'slug' => 'stored-xss',
    ]);
    $progress = LabProgress::factory()->for($user)->for($lab)->started()->create([
        'started_at' => '2026-09-03 08:00:00',
    ]);
    $otherProgress = LabProgress::factory()->for($otherUser)->for($lab)->completed()->create([
        'started_at' => '2026-09-02 08:00:00',
        'completed_at' => '2026-09-02 08:30:00',
    ]);
    $this->travelTo('2026-09-03 12:00:00');

    $this->actingAs($user)->post(route('labs.simulate', ['lab' => $lab->slug]), [
        'input' => ['comment' => '<script>alert("training")</script>'],
    ])->assertRedirect(route('labs.workspace', ['lab' => $lab->slug]));

    $this->assertDatabaseCount('lab_progress', 2);
    expect($progress->fresh()?->started_at->toDateTimeString())->toBe('2026-09-03 08:00:00')
        ->and($progress->fresh()?->completed_at)->toBeNull()
        ->and($otherProgress->fresh()?->started_at->toDateTimeString())->toBe('2026-09-02 08:00:00')
        ->and($otherProgress->fresh()?->completed_at?->toDateTimeString())->toBe('2026-09-02 08:30:00');
});

test('unsupported independent labs fail safely without progress', function () {
    $user = User::factory()->create();
    $lab = Lab::factory()->create([
        'slug' => 'independent-unsupported-lab',
    ]);
    $workspace = route('labs.workspace', ['lab' => $lab->slug]);

    $this->actingAs($user)->get($workspace)->assertInertia(fn (Assert $page) => $page
        ->where('simulation.supported', false)
        ->where('simulation.instructions', null)
        ->where('simulation.fields', [])
        ->where('simulation.result', null),
    );

    $this->actingAs($user)
        ->from($workspace)
        ->post(route('labs.simulate', ['lab' => $lab->slug]))
        ->assertRedirect($workspace)
        ->assertSessionHasErrors('simulation');

    $this->assertDatabaseCount('lab_progress', 0);
});
