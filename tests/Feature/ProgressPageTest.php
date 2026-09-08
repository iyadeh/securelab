<?php

use App\Models\Lab;
use App\Models\LabProgress;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from the progress page', function () {
    $response = $this->get(route('progress'));

    $response->assertRedirect(route('login'));
});

test('unverified users are redirected from the progress page', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get(route('progress'));

    $response->assertRedirect(route('verification.notice'));
});

test('fresh users see canonical totals and the first lesson as their next step', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('progress'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('progress')
        ->where('lessonProgress', [
            'total' => 5,
            'started' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'remaining' => 5,
            'completion_percentage' => 0,
        ])
        ->where('labProgress', [
            'total' => 5,
            'started' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'remaining' => 5,
            'completion_percentage' => 0,
        ])
        ->has('learningPaths', 1)
        ->where('learningPaths.0', [
            'slug' => 'web-security-fundamentals',
            'title' => 'Web Security Fundamentals',
            'difficulty' => 'easy',
            'module_count' => 3,
            'lesson_count' => 5,
            'completed_lessons' => 0,
            'in_progress_lessons' => 0,
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
});

test('partial lesson and lab progress is scoped to the current user', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $completedLesson = Lesson::query()
        ->where('slug', 'sql-injection-authentication-bypass')
        ->firstOrFail();
    $startedLesson = Lesson::query()->where('slug', 'reflected-xss')->firstOrFail();
    $startedLab = Lab::query()->where('slug', 'idor')->firstOrFail();
    $completedLab = Lab::query()->where('slug', 'weak-password-reset')->firstOrFail();
    $otherUserLesson = Lesson::query()->where('slug', 'stored-xss')->firstOrFail();
    $otherUserLab = Lab::query()->where('slug', 'stored-xss')->firstOrFail();
    LessonProgress::factory()->for($user)->for($completedLesson)->completed()->create();
    LessonProgress::factory()->for($user)->for($startedLesson)->started()->create();
    LabProgress::factory()->for($user)->for($startedLab)->started()->create();
    LabProgress::factory()->for($user)->for($completedLab)->completed()->create();
    LessonProgress::factory()->for($otherUser)->for($otherUserLesson)->completed()->create();
    LabProgress::factory()->for($otherUser)->for($otherUserLab)->completed()->create();

    $response = $this->actingAs($user)->get(route('progress'));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('lessonProgress', [
            'total' => 5,
            'started' => 2,
            'in_progress' => 1,
            'completed' => 1,
            'remaining' => 4,
            'completion_percentage' => 20,
        ])
        ->where('labProgress', [
            'total' => 5,
            'started' => 2,
            'in_progress' => 1,
            'completed' => 1,
            'remaining' => 4,
            'completion_percentage' => 20,
        ])
        ->where('learningPaths.0.completed_lessons', 1)
        ->where('learningPaths.0.in_progress_lessons', 1)
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
        ]),
    );
});

test('completed learning and labs show one hundred percent with no continuation items', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::factory()->create();
    Lesson::query()->get()->each(function (Lesson $lesson) use ($user): void {
        LessonProgress::factory()->for($user)->for($lesson)->completed()->create();
    });
    Lab::query()->get()->each(function (Lab $lab) use ($user): void {
        LabProgress::factory()->for($user)->for($lab)->completed()->create();
    });

    $response = $this->actingAs($user)->get(route('progress'));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('lessonProgress', [
            'total' => 5,
            'started' => 5,
            'in_progress' => 0,
            'completed' => 5,
            'remaining' => 0,
            'completion_percentage' => 100,
        ])
        ->where('labProgress', [
            'total' => 5,
            'started' => 5,
            'in_progress' => 0,
            'completed' => 5,
            'remaining' => 0,
            'completion_percentage' => 100,
        ])
        ->where('learningPaths.0.state', 'completed')
        ->where('learningPaths.0.completion_percentage', 100)
        ->where('learningPaths.0.next_lesson', null)
        ->has('inProgressLessons', 0)
        ->has('inProgressLabs', 0),
    );
});

test('empty curriculum and lab catalogs render safe zero states', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('progress'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('progress')
        ->where('lessonProgress', [
            'total' => 0,
            'started' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'remaining' => 0,
            'completion_percentage' => 0,
        ])
        ->where('labProgress', [
            'total' => 0,
            'started' => 0,
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
