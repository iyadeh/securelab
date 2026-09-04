<?php

use App\Models\Lab;
use App\Models\User;
use App\Services\Labs\Contracts\LabRuntime;
use App\Services\Labs\LabGuidanceProvider;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia as Assert;

const LAB_EXPERIENCE_IDOR_SLUG = 'idor';

const LAB_EXPERIENCE_CANONICAL_SLUGS = [
    'idor',
    'reflected-xss',
    'sql-injection-authentication-bypass',
    'stored-xss',
    'weak-password-reset',
];

function labExperienceRoute(string $routeName, string $slug = LAB_EXPERIENCE_IDOR_SLUG): string
{
    return route($routeName, ['lab' => $slug]);
}

test('fresh learners browse the canonical IDOR journey without starting progress', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::factory()->create();

    $catalogResponse = $this->actingAs($user)->get(route('labs'));
    $detailResponse = $this->actingAs($user)->get(labExperienceRoute('labs.show'));
    $workspaceResponse = $this->actingAs($user)->get(labExperienceRoute('labs.workspace'));

    $catalogResponse->assertOk()->assertInertia(fn (Assert $page) => $page
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
            fn (Collection $labs): bool => $labs->pluck('state', 'slug')->get(LAB_EXPERIENCE_IDOR_SLUG) === 'not_started',
        ),
    );
    $detailResponse->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('lab-detail')
        ->whereAll([
            'lab.slug' => LAB_EXPERIENCE_IDOR_SLUG,
            'lab.title' => 'IDOR',
            'lab.vulnerability_type' => 'access_control',
            'lab.difficulty' => 'easy',
            'lab.state' => 'not_started',
        ]),
    );
    $workspaceResponse->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('lab-workspace')
        ->where('lab.state', 'not_started')
        ->where('simulation.supported', true)
        ->where('simulation.fields.0.key', 'resource_id')
        ->where('simulation.result', null)
        ->where('simulation.guidance', null),
    );
    $this->assertDatabaseCount('lab_progress', 0);
});

test('vulnerable simulation before start remains ephemeral and does not create progress', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::factory()->create();
    $workspace = labExperienceRoute('labs.workspace');

    $simulationResponse = $this->actingAs($user)
        ->from($workspace)
        ->post(labExperienceRoute('labs.simulate'), [
            'input' => ['resource_id' => '2002'],
        ]);

    $simulationResponse->assertRedirect($workspace);
    $this->assertDatabaseCount('lab_progress', 0);
    $this->actingAs($user)->get($workspace)->assertInertia(fn (Assert $page) => $page
        ->where('lab.state', 'not_started')
        ->where('simulation.result.outcome', 'vulnerable_behavior')
        ->where('simulation.result.input', ['resource_id' => '2002'])
        ->where(
            'simulation.result.steps',
            fn (Collection $steps): bool => $steps->pluck('type')->all() === [
                'input',
                'application',
                'decision',
                'output',
            ],
        )
        ->where('simulation.guidance.security_control', 'Object-level authorization')
        ->has('simulation.guidance.root_cause')
        ->has('simulation.guidance.remediation')
        ->has('simulation.guidance.secure_behavior')
        ->missing('simulation.guidance.class')
        ->missing('simulation.guidance.provider'),
    );
    $this->actingAs($user)->get($workspace)->assertInertia(fn (Assert $page) => $page
        ->where('simulation.result', null)
        ->where('simulation.guidance', null),
    );
    $this->assertDatabaseCount('lab_progress', 0);
});

test('learners can start simulate complete and reuse the canonical IDOR lab', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::factory()->create();
    $lab = Lab::query()->where('slug', LAB_EXPERIENCE_IDOR_SLUG)->firstOrFail();
    $workspace = labExperienceRoute('labs.workspace');
    $this->travelTo('2026-09-04 09:00:00');

    $startResponse = $this->actingAs($user)->post(labExperienceRoute('labs.start'));

    $startResponse->assertRedirect($workspace);
    $this->assertDatabaseCount('lab_progress', 1);
    $this->assertDatabaseHas('lab_progress', [
        'user_id' => $user->id,
        'lab_id' => $lab->id,
        'started_at' => '2026-09-04 09:00:00',
        'completed_at' => null,
    ]);
    $progress = $user->labProgress()->whereBelongsTo($lab, 'lab')->firstOrFail();
    $this->actingAs($user)->get(route('labs'))->assertInertia(fn (Assert $page) => $page
        ->where('summary', [
            'total' => 5,
            'started' => 1,
            'in_progress' => 1,
            'completed' => 0,
            'remaining' => 5,
        ])
        ->where(
            'labs',
            fn (Collection $labs): bool => $labs->pluck('state', 'slug')->get(LAB_EXPERIENCE_IDOR_SLUG) === 'in_progress',
        ),
    );
    $this->actingAs($user)->get(labExperienceRoute('labs.show'))->assertInertia(
        fn (Assert $page) => $page->where('lab.state', 'in_progress'),
    );
    $this->actingAs($user)->get($workspace)->assertInertia(
        fn (Assert $page) => $page->where('lab.state', 'in_progress'),
    );

    $this->travelTo('2026-09-04 10:00:00');
    $this->actingAs($user)->post(labExperienceRoute('labs.simulate'), [
        'input' => ['resource_id' => '1001'],
    ])->assertRedirect($workspace);
    $this->actingAs($user)->get($workspace)->assertInertia(fn (Assert $page) => $page
        ->where('lab.state', 'in_progress')
        ->where('simulation.result.outcome', 'normal_behavior')
        ->where('simulation.result.summary', 'The fictional endpoint would return the resource owned by the synthetic learner.')
        ->where('simulation.guidance.security_control', 'Object-level authorization'),
    );
    expect($progress->fresh()?->started_at->toDateTimeString())->toBe('2026-09-04 09:00:00')
        ->and($progress->fresh()?->completed_at)->toBeNull();

    $this->travelTo('2026-09-04 10:30:00');
    $this->actingAs($user)->post(labExperienceRoute('labs.simulate'), [
        'input' => ['resource_id' => '2002'],
    ])->assertRedirect($workspace);
    $this->actingAs($user)->get($workspace)->assertInertia(fn (Assert $page) => $page
        ->where('lab.state', 'in_progress')
        ->where('simulation.result.outcome', 'vulnerable_behavior')
        ->where('simulation.guidance.security_control', 'Object-level authorization'),
    );
    expect($progress->fresh()?->started_at->toDateTimeString())->toBe('2026-09-04 09:00:00')
        ->and($progress->fresh()?->completed_at)->toBeNull();

    $this->travelTo('2026-09-04 11:00:00');
    $completeResponse = $this->actingAs($user)->post(labExperienceRoute('labs.complete'));

    $completeResponse->assertRedirect($workspace);
    $this->assertDatabaseCount('lab_progress', 1);
    $this->assertDatabaseHas('lab_progress', [
        'user_id' => $user->id,
        'lab_id' => $lab->id,
        'started_at' => '2026-09-04 09:00:00',
        'completed_at' => '2026-09-04 11:00:00',
    ]);
    $this->actingAs($user)->get(route('labs'))->assertInertia(fn (Assert $page) => $page
        ->where('summary', [
            'total' => 5,
            'started' => 1,
            'in_progress' => 0,
            'completed' => 1,
            'remaining' => 4,
        ])
        ->where(
            'labs',
            fn (Collection $labs): bool => $labs->pluck('state', 'slug')->get(LAB_EXPERIENCE_IDOR_SLUG) === 'completed',
        ),
    );
    $this->actingAs($user)->get(labExperienceRoute('labs.show'))->assertInertia(
        fn (Assert $page) => $page->where('lab.state', 'completed'),
    );
    $this->actingAs($user)->get($workspace)->assertInertia(
        fn (Assert $page) => $page->where('lab.state', 'completed'),
    );

    $this->travelTo('2026-09-04 12:00:00');
    $this->actingAs($user)->post(labExperienceRoute('labs.simulate'), [
        'input' => ['resource_id' => '2002'],
    ])->assertRedirect($workspace);
    $this->actingAs($user)->get($workspace)->assertInertia(fn (Assert $page) => $page
        ->where('lab.state', 'completed')
        ->where('simulation.result.outcome', 'vulnerable_behavior'),
    );

    $this->travelTo('2026-09-04 13:00:00');
    $this->actingAs($user)->get(route('labs'))->assertOk();
    $this->actingAs($user)->get(labExperienceRoute('labs.show'))->assertOk();
    $this->actingAs($user)->get($workspace)->assertOk();
    $this->actingAs($user)->post(labExperienceRoute('labs.simulate'), [
        'input' => ['resource_id' => '9999'],
    ])->assertRedirect($workspace);
    $this->actingAs($user)->get($workspace)->assertInertia(fn (Assert $page) => $page
        ->where('lab.state', 'completed')
        ->where('simulation.result.outcome', 'rejected')
        ->where('simulation.result.summary', 'The fictional endpoint would return a not-found response for this synthetic identifier.')
        ->where('simulation.guidance.security_control', 'Object-level authorization'),
    );

    $this->assertDatabaseCount('lab_progress', 1);
    expect($progress->fresh()?->started_at->toDateTimeString())->toBe('2026-09-04 09:00:00')
        ->and($progress->fresh()?->completed_at?->toDateTimeString())->toBe('2026-09-04 11:00:00');
});

test('repeated progress actions preserve one row stable timestamps and bounded aggregates', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::factory()->create();
    $lab = Lab::query()->where('slug', LAB_EXPERIENCE_IDOR_SLUG)->firstOrFail();
    $this->travelTo('2026-09-04 09:00:00');
    $this->actingAs($user)->post(labExperienceRoute('labs.start'))->assertRedirect();
    $this->travelTo('2026-09-04 10:00:00');
    $this->actingAs($user)->post(labExperienceRoute('labs.start'))->assertRedirect();
    $this->travelTo('2026-09-04 11:00:00');
    $this->actingAs($user)->post(labExperienceRoute('labs.complete'))->assertRedirect();
    $this->travelTo('2026-09-04 12:00:00');
    $this->actingAs($user)->post(labExperienceRoute('labs.start'))->assertRedirect();
    $this->travelTo('2026-09-04 13:00:00');

    $completeResponse = $this->actingAs($user)->post(labExperienceRoute('labs.complete'));

    $completeResponse->assertRedirect(labExperienceRoute('labs.workspace'));
    $this->assertDatabaseCount('lab_progress', 1);
    $this->assertDatabaseHas('lab_progress', [
        'user_id' => $user->id,
        'lab_id' => $lab->id,
        'started_at' => '2026-09-04 09:00:00',
        'completed_at' => '2026-09-04 11:00:00',
    ]);
    $this->actingAs($user)->get(route('labs'))->assertInertia(fn (Assert $page) => $page
        ->where('summary', [
            'total' => 5,
            'started' => 1,
            'in_progress' => 0,
            'completed' => 1,
            'remaining' => 4,
        ]),
    );
});

test('lab progress and read-only simulation stay isolated between learners', function () {
    $this->seed(DatabaseSeeder::class);
    $progressedUser = User::factory()->create();
    $freshUser = User::factory()->create();
    $lab = Lab::query()->where('slug', LAB_EXPERIENCE_IDOR_SLUG)->firstOrFail();
    $workspace = labExperienceRoute('labs.workspace');
    $this->travelTo('2026-09-04 09:00:00');
    $this->actingAs($progressedUser)->post(labExperienceRoute('labs.start'))->assertRedirect();
    $this->actingAs($progressedUser)->post(labExperienceRoute('labs.simulate'), [
        'input' => ['resource_id' => '2002'],
    ])->assertRedirect($workspace);
    $this->actingAs($progressedUser)->get($workspace)->assertInertia(
        fn (Assert $page) => $page->where('simulation.result.outcome', 'vulnerable_behavior'),
    );
    $this->travelTo('2026-09-04 10:00:00');
    $this->actingAs($progressedUser)->post(labExperienceRoute('labs.complete'))->assertRedirect();
    $progress = $progressedUser->labProgress()->whereBelongsTo($lab, 'lab')->firstOrFail();
    $this->travelTo('2026-09-04 12:00:00');

    $catalogResponse = $this->actingAs($freshUser)->get(route('labs'));
    $detailResponse = $this->actingAs($freshUser)->get(labExperienceRoute('labs.show'));
    $workspaceResponse = $this->actingAs($freshUser)->get($workspace);
    $this->actingAs($freshUser)->post(labExperienceRoute('labs.simulate'), [
        'input' => ['resource_id' => '2002'],
    ])->assertRedirect($workspace);
    $simulationResponse = $this->actingAs($freshUser)->get($workspace);

    $catalogResponse->assertInertia(fn (Assert $page) => $page
        ->where('summary', [
            'total' => 5,
            'started' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'remaining' => 5,
        ])
        ->where(
            'labs',
            fn (Collection $labs): bool => $labs->pluck('state', 'slug')->get(LAB_EXPERIENCE_IDOR_SLUG) === 'not_started',
        ),
    );
    $detailResponse->assertInertia(
        fn (Assert $page) => $page->where('lab.state', 'not_started'),
    );
    $workspaceResponse->assertInertia(
        fn (Assert $page) => $page->where('lab.state', 'not_started'),
    );
    $simulationResponse->assertInertia(fn (Assert $page) => $page
        ->where('lab.state', 'not_started')
        ->where('simulation.result.outcome', 'vulnerable_behavior'),
    );
    $this->assertDatabaseCount('lab_progress', 1);
    $this->assertDatabaseMissing('lab_progress', [
        'user_id' => $freshUser->id,
        'lab_id' => $lab->id,
    ]);
    expect($progress->fresh()?->started_at->toDateTimeString())->toBe('2026-09-04 09:00:00')
        ->and($progress->fresh()?->completed_at?->toDateTimeString())->toBe('2026-09-04 10:00:00');
});

test('canonical catalog identities remain wired to supported runtime and guidance', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::factory()->create();
    $runtime = $this->app->make(LabRuntime::class);
    $guidanceProvider = $this->app->make(LabGuidanceProvider::class);
    $expectedControls = [
        'idor' => 'Object-level authorization',
        'reflected-xss' => 'Context-appropriate output encoding',
        'sql-injection-authentication-bypass' => 'Parameterized database access',
        'stored-xss' => 'Safe rendering of stored content',
        'weak-password-reset' => 'Account-bound reset-token validation',
    ];
    $labs = Lab::query()->orderBy('title')->orderBy('slug')->get();

    $this->actingAs($user)->get(route('labs'))->assertInertia(fn (Assert $page) => $page
        ->where(
            'labs',
            fn (Collection $catalogLabs): bool => $catalogLabs->pluck('slug')->all() === LAB_EXPERIENCE_CANONICAL_SLUGS,
        ),
    );
    expect($labs)->toHaveCount(5);

    foreach ($labs as $lab) {
        $this->actingAs($user)->get(labExperienceRoute('labs.workspace', $lab->slug))
            ->assertInertia(fn (Assert $page) => $page->where('simulation.supported', true));
        expect($runtime->definition($lab))->not->toBeNull()
            ->and($guidanceProvider->guidanceFor($lab)?->securityControl)->toBe($expectedControls[$lab->slug]);
    }

    $this->assertDatabaseCount('lab_progress', 0);
});
