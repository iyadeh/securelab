<?php

use App\Models\Lab;
use App\Models\LabProgress;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
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
        ->where('learningPaths.0', [
            'slug' => 'web-security-fundamentals',
            'title' => 'Web Security Fundamentals',
            'module_count' => 3,
            'lesson_count' => 5,
            'completed_lessons' => 0,
            'completion_percentage' => 0,
            'state' => 'not_started',
            'next_lesson' => [
                'slug' => 'sql-injection-authentication-bypass',
                'title' => 'SQL Injection Authentication Bypass',
            ],
        ])
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
    LessonProgress::factory()->for($user)->for($completedLesson)->completed()->create();
    LessonProgress::factory()->for($user)->for($startedLesson)->started()->create();
    LabProgress::factory()->for($user)->for($startedLab)->started()->create();
    LabProgress::factory()->for($user)->for($completedLab)->completed()->create();
    LessonProgress::factory()->for($otherUser)->for($otherLesson)->completed()->create();
    LabProgress::factory()->for($otherUser)->for($otherLab)->completed()->create();

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
        ->where('learningPaths.0.completed_lessons', 1)
        ->where('learningPaths.0.completion_percentage', 20)
        ->where('learningPaths.0.state', 'in_progress')
        ->where('learningPaths.0.next_lesson', [
            'slug' => 'reflected-xss',
            'title' => 'Reflected XSS',
        ])
        ->where('inProgressLessons.0', [
            'slug' => 'reflected-xss',
            'title' => 'Reflected XSS',
            'module_title' => 'Cross-Site Scripting',
            'learning_path' => [
                'slug' => 'web-security-fundamentals',
                'title' => 'Web Security Fundamentals',
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
        ->where('learningPaths.0.state', 'completed')
        ->where('learningPaths.0.next_lesson', null)
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

    $this->actingAs($user)->get(route('dashboard'))->assertOk();
    $this->actingAs($user)->get(route('dashboard'))->assertOk();

    expect($lessonProgress->fresh()?->started_at->toDateTimeString())
        ->toBe('2026-09-08 08:00:00')
        ->and($lessonProgress->fresh()?->completed_at)->toBeNull()
        ->and($labProgress->fresh()?->started_at->toDateTimeString())
        ->toBe('2026-09-08 09:00:00')
        ->and($labProgress->fresh()?->completed_at?->toDateTimeString())
        ->toBe('2026-09-08 09:30:00');
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
        ->has('learningPaths', 0)
        ->has('inProgressLessons', 0)
        ->has('inProgressLabs', 0),
    );
});
