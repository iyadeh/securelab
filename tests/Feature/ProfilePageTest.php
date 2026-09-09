<?php

use App\Models\Achievement;
use App\Models\Lab;
use App\Models\LabProgress;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use App\Models\UserAchievement;
use Database\Seeders\AchievementSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\LabSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from the profile page', function () {
    $response = $this->get(route('profile.show'));

    $response->assertRedirect(route('login'));
});

test('unverified users are redirected from the profile page', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get(route('profile.show'));

    $response->assertRedirect(route('verification.notice'));
});

test('fresh users see their identity and truthful canonical summary', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::factory()->create([
        'name' => 'Arga Pratama',
        'email' => 'arga@example.com',
        'created_at' => '2026-09-01 10:00:00',
    ]);

    $response = $this->actingAs($user)->get(route('profile.show'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('profile')
        ->where('learner', [
            'name' => 'Arga Pratama',
            'email' => 'arga@example.com',
            'joined_at' => '2026-09-01T10:00:00.000000Z',
        ])
        ->where('learningSummary', [
            'total' => 5,
            'in_progress' => 0,
            'completed' => 0,
            'remaining' => 5,
            'completion_percentage' => 0,
        ])
        ->where('labSummary', [
            'total' => 5,
            'in_progress' => 0,
            'completed' => 0,
            'remaining' => 5,
            'completion_percentage' => 0,
        ])
        ->where('achievementSummary', [
            'total' => 5,
            'unlocked' => 0,
            'locked' => 5,
        ])
        ->where('learningPaths.0', [
            'slug' => 'web-security-fundamentals',
            'title' => 'Web Security Fundamentals',
            'lesson_count' => 5,
            'completed_lessons' => 0,
            'completion_percentage' => 0,
            'state' => 'not_started',
        ])
        ->missing('learner.id')
        ->missing('learner.password')
        ->missing('learningPaths.0.id'),
    );
    $this->assertDatabaseCount('lesson_progress', 0);
    $this->assertDatabaseCount('lab_progress', 0);
    $this->assertDatabaseCount('user_achievements', 0);
});

test('partial progress and persisted awards produce a concise profile summary', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::factory()->create();
    $completedLesson = Lesson::query()
        ->where('slug', 'sql-injection-authentication-bypass')
        ->firstOrFail();
    $startedLesson = Lesson::query()->where('slug', 'reflected-xss')->firstOrFail();
    $completedLab = Lab::query()->where('slug', 'weak-password-reset')->firstOrFail();
    $startedLab = Lab::query()->where('slug', 'idor')->firstOrFail();
    $achievement = Achievement::query()->where('slug', 'first-lesson')->firstOrFail();
    LessonProgress::factory()->for($user)->for($completedLesson)->completed()->create();
    LessonProgress::factory()->for($user)->for($startedLesson)->started()->create();
    LabProgress::factory()->for($user)->for($completedLab)->completed()->create();
    LabProgress::factory()->for($user)->for($startedLab)->started()->create();
    UserAchievement::factory()->for($user)->for($achievement)->create();

    $response = $this->actingAs($user)->get(route('profile.show'));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('learningSummary', [
            'total' => 5,
            'in_progress' => 1,
            'completed' => 1,
            'remaining' => 4,
            'completion_percentage' => 20,
        ])
        ->where('labSummary', [
            'total' => 5,
            'in_progress' => 1,
            'completed' => 1,
            'remaining' => 4,
            'completion_percentage' => 20,
        ])
        ->where('achievementSummary', [
            'total' => 5,
            'unlocked' => 1,
            'locked' => 4,
        ])
        ->where('learningPaths.0.completed_lessons', 1)
        ->where('learningPaths.0.completion_percentage', 20)
        ->where('learningPaths.0.state', 'in_progress'),
    );
});

test('completed users see completed learning and lab summaries', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::factory()->create();

    Lesson::query()->get()->each(function (Lesson $lesson) use ($user): void {
        LessonProgress::factory()->for($user)->for($lesson)->completed()->create();
    });
    Lab::query()->get()->each(function (Lab $lab) use ($user): void {
        LabProgress::factory()->for($user)->for($lab)->completed()->create();
    });
    Achievement::query()->get()->each(function (Achievement $achievement) use ($user): void {
        UserAchievement::factory()->for($user)->for($achievement)->create();
    });

    $response = $this->actingAs($user)->get(route('profile.show'));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('learningSummary', [
            'total' => 5,
            'in_progress' => 0,
            'completed' => 5,
            'remaining' => 0,
            'completion_percentage' => 100,
        ])
        ->where('labSummary', [
            'total' => 5,
            'in_progress' => 0,
            'completed' => 5,
            'remaining' => 0,
            'completion_percentage' => 100,
        ])
        ->where('achievementSummary', [
            'total' => 5,
            'unlocked' => 5,
            'locked' => 0,
        ])
        ->where('learningPaths.0.state', 'completed')
        ->where('learningPaths.0.completion_percentage', 100),
    );
});

test('identity progress and awards remain isolated to the authenticated user', function () {
    $this->seed(DatabaseSeeder::class);
    $progressedUser = User::factory()->create(['name' => 'Progressed Learner']);
    $freshUser = User::factory()->create([
        'name' => 'Fresh Learner',
        'email' => 'fresh@example.com',
    ]);
    $lesson = Lesson::query()->where('slug', 'idor')->firstOrFail();
    $lab = Lab::query()->where('slug', 'idor')->firstOrFail();
    $achievement = Achievement::query()->where('slug', 'first-lesson')->firstOrFail();
    LessonProgress::factory()->for($progressedUser)->for($lesson)->completed()->create();
    LabProgress::factory()->for($progressedUser)->for($lab)->completed()->create();
    UserAchievement::factory()->for($progressedUser)->for($achievement)->create();

    $response = $this->actingAs($freshUser)->get(route('profile.show'));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('learner.name', 'Fresh Learner')
        ->where('learner.email', 'fresh@example.com')
        ->where('learningSummary.completed', 0)
        ->where('labSummary.completed', 0)
        ->where('achievementSummary.unlocked', 0)
        ->where('learningPaths.0.state', 'not_started'),
    );
});

test('repeated profile requests preserve progress and award timestamps', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::factory()->create();
    $lesson = Lesson::query()->where('slug', 'reflected-xss')->firstOrFail();
    $lab = Lab::query()->where('slug', 'idor')->firstOrFail();
    $achievement = Achievement::query()->where('slug', 'first-lesson')->firstOrFail();
    $lessonProgress = LessonProgress::factory()->for($user)->for($lesson)->create([
        'started_at' => '2026-09-08 08:00:00',
        'completed_at' => null,
    ]);
    $labProgress = LabProgress::factory()->for($user)->for($lab)->create([
        'started_at' => '2026-09-08 09:00:00',
        'completed_at' => '2026-09-08 09:30:00',
    ]);
    $award = UserAchievement::factory()->for($user)->for($achievement)->create([
        'unlocked_at' => '2026-09-08 10:00:00',
    ]);

    $this->actingAs($user)->get(route('profile.show'))->assertOk();
    $this->actingAs($user)->get(route('profile.show'))->assertOk();

    expect($lessonProgress->fresh()?->started_at->toDateTimeString())
        ->toBe('2026-09-08 08:00:00')
        ->and($lessonProgress->fresh()?->completed_at)->toBeNull()
        ->and($labProgress->fresh()?->started_at->toDateTimeString())
        ->toBe('2026-09-08 09:00:00')
        ->and($labProgress->fresh()?->completed_at?->toDateTimeString())
        ->toBe('2026-09-08 09:30:00')
        ->and($award->fresh()?->unlocked_at->toDateTimeString())
        ->toBe('2026-09-08 10:00:00');
    $this->assertDatabaseCount('lesson_progress', 1);
    $this->assertDatabaseCount('lab_progress', 1);
    $this->assertDatabaseCount('user_achievements', 1);
});

test('eligible progress is not backfilled into achievements by profile requests', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::factory()->create();
    $lesson = Lesson::query()
        ->where('slug', 'sql-injection-authentication-bypass')
        ->firstOrFail();
    LessonProgress::factory()->for($user)->for($lesson)->completed()->create();

    $response = $this->actingAs($user)->get(route('profile.show'));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('learningSummary.completed', 1)
        ->where('achievementSummary', [
            'total' => 5,
            'unlocked' => 0,
            'locked' => 5,
        ]),
    );
    $this->assertDatabaseCount('user_achievements', 0);
});

test('empty curriculum renders safely alongside available labs and achievements', function () {
    $this->seed([LabSeeder::class, AchievementSeeder::class]);
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('profile.show'));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('learningSummary.total', 0)
        ->where('labSummary.total', 5)
        ->where('achievementSummary.total', 5)
        ->has('learningPaths', 0),
    );
});

test('empty lab catalog renders safely alongside available curriculum', function () {
    $this->seed(DatabaseSeeder::class);
    Lab::query()->delete();
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('profile.show'));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('learningSummary.total', 5)
        ->where('labSummary', [
            'total' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'remaining' => 0,
            'completion_percentage' => 0,
        ])
        ->where('achievementSummary.total', 5)
        ->has('learningPaths', 1),
    );
});

test('empty achievement catalog renders safely alongside available learning', function () {
    $this->seed(DatabaseSeeder::class);
    Achievement::query()->delete();
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('profile.show'));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('learningSummary.total', 5)
        ->where('labSummary.total', 5)
        ->where('achievementSummary', [
            'total' => 0,
            'unlocked' => 0,
            'locked' => 0,
        ])
        ->has('learningPaths', 1),
    );
});

test('learning paths use deterministic title and slug ordering instead of database IDs', function () {
    $user = User::factory()->create();
    LearningPath::factory()->create(['slug' => 'zulu-path', 'title' => 'Zulu Path']);
    LearningPath::factory()->create(['slug' => 'alpha-z', 'title' => 'Alpha Path']);
    LearningPath::factory()->create(['slug' => 'alpha-a', 'title' => 'Alpha Path']);

    $response = $this->actingAs($user)->get(route('profile.show'));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('learningPaths.0.slug', 'alpha-a')
        ->where('learningPaths.1.slug', 'alpha-z')
        ->where('learningPaths.2.slug', 'zulu-path'),
    );
});
