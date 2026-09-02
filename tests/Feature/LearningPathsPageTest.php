<?php

use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('verified users see canonical learning path data with fresh progress', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('learn'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('learning-paths')
        ->has('learningPaths', 1)
        ->where('learningPaths.0', [
            'slug' => 'web-security-fundamentals',
            'title' => 'Web Security Fundamentals',
            'description' => 'Learn foundational web security by tracing unsafe input, broken authorization, and insecure account workflows from root cause to remediation.',
            'difficulty' => 'easy',
            'module_count' => 3,
            'lesson_count' => 5,
            'estimated_minutes' => 90,
            'completed_lessons' => 0,
            'completion_percentage' => 0,
            'is_complete' => false,
            'next_lesson' => [
                'title' => 'SQL Injection Authentication Bypass',
            ],
        ]),
    );
});

test('partial progress and next lesson belong only to the current user', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $completedLessons = Lesson::query()
        ->whereIn('slug', ['sql-injection-authentication-bypass', 'reflected-xss'])
        ->get();
    $startedLesson = Lesson::query()->where('slug', 'stored-xss')->firstOrFail();

    $completedLessons->each(function (Lesson $lesson) use ($user): void {
        LessonProgress::factory()->for($user)->for($lesson)->completed()->create();
    });
    LessonProgress::factory()->for($user)->for($startedLesson)->started()->create();
    Lesson::query()->get()->each(function (Lesson $lesson) use ($otherUser): void {
        LessonProgress::factory()->for($otherUser)->for($lesson)->completed()->create();
    });

    $response = $this->actingAs($user)->get(route('learn'));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('learningPaths.0.completed_lessons', 2)
        ->where('learningPaths.0.completion_percentage', 40)
        ->where('learningPaths.0.is_complete', false)
        ->where('learningPaths.0.next_lesson', [
            'title' => 'Stored XSS',
        ]),
    );
});

test('completed learning paths expose one hundred percent progress', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::factory()->create();

    Lesson::query()->get()->each(function (Lesson $lesson) use ($user): void {
        LessonProgress::factory()->for($user)->for($lesson)->completed()->create();
    });

    $response = $this->actingAs($user)->get(route('learn'));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('learningPaths.0.completed_lessons', 5)
        ->where('learningPaths.0.completion_percentage', 100)
        ->where('learningPaths.0.is_complete', true)
        ->where('learningPaths.0.next_lesson', null),
    );
});

test('learning paths page renders an empty collection without curriculum', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('learn'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('learning-paths')
        ->has('learningPaths', 0),
    );
});
