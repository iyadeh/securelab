<?php

use App\Models\Achievement;
use App\Models\Lab;
use App\Models\LabProgress;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use App\Models\UserAchievement;
use Database\Seeders\DatabaseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('unverified users are redirected from the dashboard', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertRedirect(route('verification.notice'));
});

test('fresh users see canonical totals and truthful getting started data', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
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
            'latest_unlocked' => null,
        ])
        ->where('learningPaths.0.slug', 'web-security-fundamentals')
        ->where('learningPaths.0.title', 'Web Security Fundamentals')
        ->where('learningPaths.0.difficulty', 'easy')
        ->where('learningPaths.0.module_count', 3)
        ->where('learningPaths.0.lesson_count', 5)
        ->where('learningPaths.0.completed_lessons', 0)
        ->where('learningPaths.0.in_progress_lessons', 0)
        ->where('learningPaths.0.completion_percentage', 0)
        ->where('learningPaths.0.state', 'not_started')
        ->where('learningPaths.0.next_lesson.slug', 'sql-injection-authentication-bypass')
        ->where('learningPaths.0.next_lesson.title', 'SQL Injection Authentication Bypass')
        ->where('learningPaths.0.next_lesson.module_title', 'Injection')
        ->where(
            'learningPaths.0.next_lesson.summary',
            'Understand how unsafe query construction can turn login input into an authentication bypass.',
        )
        ->where('learningPaths.0.modules.0', [
            'title' => 'Injection',
            'lesson_count' => 1,
            'completed_lessons' => 0,
            'in_progress_lessons' => 0,
            'completion_percentage' => 0,
            'state' => 'not_started',
        ])
        ->where('learningPaths.0.modules.1.lesson_count', 2)
        ->where('learningPaths.0.modules.1.state', 'not_started')
        ->where('learningPaths.0.modules.2.lesson_count', 2)
        ->where('learningPaths.0.modules.2.state', 'not_started')
        ->has('learningPaths.0.modules', 3)
        ->has('inProgressLessons', 0)
        ->has('inProgressLabs', 0)
        ->missing('learningPaths.0.id'),
    );
    $this->assertDatabaseCount('lesson_progress', 0);
    $this->assertDatabaseCount('lab_progress', 0);
});

test('partial progress and continuation data are scoped to the current user', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $completedLesson = Lesson::query()
        ->where('slug', 'sql-injection-authentication-bypass')
        ->firstOrFail();
    $startedLesson = Lesson::query()->where('slug', 'reflected-xss')->firstOrFail();
    $startedLab = Lab::query()->where('slug', 'idor')->firstOrFail();
    $completedLab = Lab::query()->where('slug', 'weak-password-reset')->firstOrFail();
    $otherLesson = Lesson::query()->where('slug', 'stored-xss')->firstOrFail();
    $otherLab = Lab::query()->where('slug', 'stored-xss')->firstOrFail();
    $firstLessonAchievement = Achievement::query()->where('slug', 'first-lesson')->firstOrFail();
    $firstLabAchievement = Achievement::query()->where('slug', 'first-lab')->firstOrFail();
    LessonProgress::factory()->for($user)->for($completedLesson)->completed()->create();
    LessonProgress::factory()->for($user)->for($startedLesson)->started()->create();
    LabProgress::factory()->for($user)->for($startedLab)->started()->create();
    LabProgress::factory()->for($user)->for($completedLab)->completed()->create();
    LessonProgress::factory()->for($otherUser)->for($otherLesson)->completed()->create();
    LabProgress::factory()->for($otherUser)->for($otherLab)->completed()->create();
    UserAchievement::factory()->for($user)->for($firstLessonAchievement)->create([
        'unlocked_at' => '2026-09-08 10:00:00',
    ]);
    UserAchievement::factory()->for($otherUser)->for($firstLabAchievement)->create([
        'unlocked_at' => '2026-09-08 11:00:00',
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

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
            'latest_unlocked' => [
                'slug' => 'first-lesson',
                'title' => 'First Lesson',
            ],
        ])
        ->where('learningPaths.0.completed_lessons', 1)
        ->where('learningPaths.0.in_progress_lessons', 1)
        ->where('learningPaths.0.completion_percentage', 20)
        ->where('learningPaths.0.state', 'in_progress')
        ->where('learningPaths.0.next_lesson.slug', 'reflected-xss')
        ->where('learningPaths.0.next_lesson.title', 'Reflected XSS')
        ->where('learningPaths.0.next_lesson.module_title', 'Cross-Site Scripting')
        ->where('learningPaths.0.modules.0.state', 'completed')
        ->where('learningPaths.0.modules.0.completion_percentage', 100)
        ->where('learningPaths.0.modules.1.state', 'in_progress')
        ->where('learningPaths.0.modules.1.in_progress_lessons', 1)
        ->where('learningPaths.0.modules.2.state', 'not_started')
        ->where('inProgressLessons.0', [
            'slug' => 'reflected-xss',
            'title' => 'Reflected XSS',
            'summary' => 'Understand how reflected input becomes active browser content when output encoding is missing.',
            'module_title' => 'Cross-Site Scripting',
            'learning_path' => [
                'slug' => 'web-security-fundamentals',
                'title' => 'Web Security Fundamentals',
                'completed_lessons' => 1,
                'lesson_count' => 5,
                'completion_percentage' => 20,
            ],
        ])
        ->where('inProgressLabs.0', [
            'slug' => 'idor',
            'title' => 'IDOR',
            'vulnerability_type' => 'access_control',
        ])
        ->has('inProgressLessons', 1)
        ->has('inProgressLabs', 1),
    );
});

test('completed learning and labs show one hundred percent without fake continuation', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::factory()->create();
    Lesson::query()->get()->each(function (Lesson $lesson) use ($user): void {
        LessonProgress::factory()->for($user)->for($lesson)->completed()->create();
    });
    Lab::query()->get()->each(function (Lab $lab) use ($user): void {
        LabProgress::factory()->for($user)->for($lab)->completed()->create();
    });
    Achievement::query()
        ->orderBy('position')
        ->get()
        ->each(function (Achievement $achievement, int $position) use ($user): void {
            UserAchievement::factory()->for($user)->for($achievement)->create([
                'unlocked_at' => now()->addMinutes($position),
            ]);
        });

    $response = $this->actingAs($user)->get(route('dashboard'));

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
        ->where('achievementSummary.total', 5)
        ->where('achievementSummary.unlocked', 5)
        ->where('achievementSummary.locked', 0)
        ->where('achievementSummary.latest_unlocked.slug', 'security-foundations-complete')
        ->where('learningPaths.0.state', 'completed')
        ->where('learningPaths.0.next_lesson', null)
        ->where('learningPaths.0.modules.0.state', 'completed')
        ->where('learningPaths.0.modules.1.state', 'completed')
        ->where('learningPaths.0.modules.2.state', 'completed')
        ->has('inProgressLessons', 0)
        ->has('inProgressLabs', 0),
    );
});

test('dashboard requests preserve existing progress timestamps', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::factory()->create();
    $lesson = Lesson::query()->where('slug', 'reflected-xss')->firstOrFail();
    $lab = Lab::query()->where('slug', 'idor')->firstOrFail();
    $lessonProgress = LessonProgress::factory()->for($user)->for($lesson)->create([
        'started_at' => '2026-09-08 08:00:00',
        'completed_at' => null,
    ]);
    $labProgress = LabProgress::factory()->for($user)->for($lab)->create([
        'started_at' => '2026-09-08 09:00:00',
        'completed_at' => '2026-09-08 09:30:00',
    ]);
    $achievement = Achievement::query()->where('slug', 'first-lab')->firstOrFail();
    $award = UserAchievement::factory()->for($user)->for($achievement)->create([
        'unlocked_at' => '2026-09-08 10:00:00',
    ]);

    $this->actingAs($user)->get(route('dashboard'))->assertOk();
    $this->actingAs($user)->get(route('dashboard'))->assertOk();

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
});

test('empty curriculum and lab catalogs render safe zero states', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->where('learningSummary', [
            'total' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'remaining' => 0,
            'completion_percentage' => 0,
        ])
        ->where('labSummary', [
            'total' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'remaining' => 0,
            'completion_percentage' => 0,
        ])
        ->where('achievementSummary', [
            'total' => 0,
            'unlocked' => 0,
            'locked' => 0,
            'latest_unlocked' => null,
        ])
        ->has('learningPaths', 0)
        ->has('inProgressLessons', 0)
        ->has('inProgressLabs', 0),
    );
});
