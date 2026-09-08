<?php

use App\Models\Achievement;
use App\Models\Lab;
use App\Models\LabProgress;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use App\Models\UserAchievement;
use Database\Seeders\AchievementSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia as Assert;

const ACHIEVEMENTS_PAGE_CANONICAL_SLUGS = [
    'first-lesson',
    'first-lab',
    'path-complete',
    'lab-series-complete',
    'security-foundations-complete',
];

test('guests are redirected from the achievements page', function () {
    $response = $this->get(route('achievements'));

    $response->assertRedirect(route('login'));
});

test('unverified users are redirected from the achievements page', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get(route('achievements'));

    $response->assertRedirect(route('verification.notice'));
});

test('fresh users see the ordered canonical catalog as locked', function () {
    $this->seed(AchievementSeeder::class);
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('achievements'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('achievements')
        ->where('summary', [
            'total' => 5,
            'unlocked' => 0,
            'locked' => 5,
        ])
        ->has('achievements', 5)
        ->where('achievements.0', [
            'slug' => 'first-lesson',
            'title' => 'First Lesson',
            'description' => 'Complete your first SecureLab lesson.',
            'category' => 'learning',
            'position' => 1,
            'state' => 'locked',
            'unlocked_at' => null,
        ])
        ->where(
            'achievements',
            fn (Collection $achievements): bool => $achievements->pluck('slug')->all()
                === ACHIEVEMENTS_PAGE_CANONICAL_SLUGS
                && $achievements->every(
                    fn (array $achievement): bool => $achievement['state'] === 'locked'
                        && $achievement['unlocked_at'] === null,
                ),
        )
        ->missing('achievements.0.id')
        ->missing('achievements.0.achievement_id')
        ->missing('achievements.0.user_id'),
    );
    $this->assertDatabaseCount('user_achievements', 0);
});

test('persisted awards alone determine partial unlocked state', function () {
    $this->seed(AchievementSeeder::class);
    $user = User::factory()->create();
    $firstLesson = Achievement::query()->where('slug', 'first-lesson')->firstOrFail();
    $firstLab = Achievement::query()->where('slug', 'first-lab')->firstOrFail();
    UserAchievement::factory()->for($user)->for($firstLesson)->create([
        'unlocked_at' => '2026-09-08 10:00:00',
    ]);
    UserAchievement::factory()->for($user)->for($firstLab)->create([
        'unlocked_at' => '2026-09-08 11:00:00',
    ]);

    $response = $this->actingAs($user)->get(route('achievements'));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('summary', [
            'total' => 5,
            'unlocked' => 2,
            'locked' => 3,
        ])
        ->where(
            'achievements',
            fn (Collection $achievements): bool => $achievements->pluck('state', 'slug')->all() === [
                'first-lesson' => 'unlocked',
                'first-lab' => 'unlocked',
                'path-complete' => 'locked',
                'lab-series-complete' => 'locked',
                'security-foundations-complete' => 'locked',
            ] && $achievements->pluck('unlocked_at', 'slug')->all() === [
                'first-lesson' => '2026-09-08T10:00:00.000000Z',
                'first-lab' => '2026-09-08T11:00:00.000000Z',
                'path-complete' => null,
                'lab-series-complete' => null,
                'security-foundations-complete' => null,
            ],
        ),
    );
});

test('fully awarded users see every achievement unlocked', function () {
    $this->seed(AchievementSeeder::class);
    $user = User::factory()->create();

    foreach (Achievement::query()->get() as $achievement) {
        UserAchievement::factory()->for($user)->for($achievement)->create();
    }

    $response = $this->actingAs($user)->get(route('achievements'));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('summary', [
            'total' => 5,
            'unlocked' => 5,
            'locked' => 0,
        ])
        ->where(
            'achievements',
            fn (Collection $achievements): bool => $achievements->every(
                fn (array $achievement): bool => $achievement['state'] === 'unlocked'
                    && $achievement['unlocked_at'] !== null,
            ),
        ),
    );
});

test('awards remain isolated to the authenticated user', function () {
    $this->seed(AchievementSeeder::class);
    $awardedUser = User::factory()->create();
    $freshUser = User::factory()->create();
    $achievement = Achievement::query()->where('slug', 'first-lesson')->firstOrFail();
    UserAchievement::factory()->for($awardedUser)->for($achievement)->create();

    $response = $this->actingAs($freshUser)->get(route('achievements'));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('summary', [
            'total' => 5,
            'unlocked' => 0,
            'locked' => 5,
        ])
        ->where(
            'achievements',
            fn (Collection $achievements): bool => $achievements->every(
                fn (array $item): bool => $item['state'] === 'locked',
            ),
        ),
    );
});

test('catalog ordering uses position then slug instead of database IDs', function () {
    $user = User::factory()->create();
    Achievement::factory()->create(['slug' => 'position-two', 'position' => 2]);
    Achievement::factory()->create(['slug' => 'position-one-z', 'position' => 1]);
    Achievement::factory()->create(['slug' => 'position-one-a', 'position' => 1]);

    $response = $this->actingAs($user)->get(route('achievements'));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('summary', [
            'total' => 3,
            'unlocked' => 0,
            'locked' => 3,
        ])
        ->where(
            'achievements',
            fn (Collection $achievements): bool => $achievements->pluck('slug')->all() === [
                'position-one-a',
                'position-one-z',
                'position-two',
            ],
        ),
    );
});

test('eligible progress is not backfilled by the read-only page', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::factory()->create();

    foreach (Lesson::query()->get() as $lesson) {
        LessonProgress::factory()->for($user)->for($lesson)->completed()->create();
    }

    foreach (Lab::query()->get() as $lab) {
        LabProgress::factory()->for($user)->for($lab)->completed()->create();
    }

    $response = $this->actingAs($user)->get(route('achievements'));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('summary', [
            'total' => 5,
            'unlocked' => 0,
            'locked' => 5,
        ])
        ->where(
            'achievements',
            fn (Collection $achievements): bool => $achievements->every(
                fn (array $achievement): bool => $achievement['state'] === 'locked',
            ),
        ),
    );
    $this->assertDatabaseCount('user_achievements', 0);
});

test('repeated page visits preserve awards and progress timestamps', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::factory()->create();
    $achievement = Achievement::query()->where('slug', 'first-lesson')->firstOrFail();
    $lesson = Lesson::query()->where('slug', 'reflected-xss')->firstOrFail();
    $lab = Lab::query()->where('slug', 'idor')->firstOrFail();
    $award = UserAchievement::factory()->for($user)->for($achievement)->create([
        'unlocked_at' => '2026-09-08 08:00:00',
    ]);
    $lessonProgress = LessonProgress::factory()->for($user)->for($lesson)->started()->create([
        'started_at' => '2026-09-08 09:00:00',
    ]);
    $labProgress = LabProgress::factory()->for($user)->for($lab)->completed()->create([
        'started_at' => '2026-09-08 10:00:00',
        'completed_at' => '2026-09-08 10:30:00',
    ]);
    $this->travelTo('2026-09-08 12:00:00');

    $this->actingAs($user)->get(route('achievements'))->assertOk();
    $this->actingAs($user)->get(route('achievements'))->assertOk();

    expect($award->fresh()?->unlocked_at->toDateTimeString())->toBe('2026-09-08 08:00:00')
        ->and($lessonProgress->fresh()?->started_at->toDateTimeString())->toBe('2026-09-08 09:00:00')
        ->and($lessonProgress->fresh()?->completed_at)->toBeNull()
        ->and($labProgress->fresh()?->started_at->toDateTimeString())->toBe('2026-09-08 10:00:00')
        ->and($labProgress->fresh()?->completed_at?->toDateTimeString())->toBe('2026-09-08 10:30:00');
    $this->assertDatabaseCount('user_achievements', 1);
    $this->assertDatabaseCount('lesson_progress', 1);
    $this->assertDatabaseCount('lab_progress', 1);
});

test('empty achievement catalogs render safe zero state', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('achievements'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('achievements')
        ->where('summary', [
            'total' => 0,
            'unlocked' => 0,
            'locked' => 0,
        ])
        ->has('achievements', 0),
    );
});
