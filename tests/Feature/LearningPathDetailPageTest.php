<?php

use App\Models\LearningModule;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from learning path detail', function () {
    $learningPath = LearningPath::factory()->create();

    $response = $this->get(route('learning-paths.show', [
        'learningPath' => $learningPath->slug,
    ]));

    $response->assertRedirect(route('login'));
});

test('unverified users are redirected from learning path detail', function () {
    $user = User::factory()->unverified()->create();
    $learningPath = LearningPath::factory()->create();

    $response = $this->actingAs($user)->get(route('learning-paths.show', [
        'learningPath' => $learningPath->slug,
    ]));

    $response->assertRedirect(route('verification.notice'));
});

test('verified users can view the canonical learning path by slug', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('learning-paths.show', [
        'learningPath' => 'web-security-fundamentals',
    ]));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('learning-path-detail')
        ->whereAll([
            'learningPath.slug' => 'web-security-fundamentals',
            'learningPath.title' => 'Web Security Fundamentals',
            'learningPath.description' => 'Learn foundational web security by tracing unsafe input, broken authorization, and insecure account workflows from root cause to remediation.',
            'learningPath.difficulty' => 'easy',
            'learningPath.module_count' => 3,
            'learningPath.lesson_count' => 5,
            'learningPath.estimated_minutes' => 90,
            'learningPath.progress' => [
                'total' => 5,
                'started' => 0,
                'in_progress' => 0,
                'completed' => 0,
                'remaining' => 5,
                'completion_percentage' => 0,
            ],
            'learningPath.is_complete' => false,
            'learningPath.next_lesson' => [
                'slug' => 'sql-injection-authentication-bypass',
                'title' => 'SQL Injection Authentication Bypass',
            ],
        ])
        ->has('learningPath.modules', 3)
        ->where(
            'learningPath.modules',
            fn (Collection $modules): bool => $modules->pluck('title')->all() === [
                'Injection',
                'Cross-Site Scripting',
                'Access & Account Security',
            ],
        )
        ->where(
            'learningPath.modules.1.lessons',
            fn (Collection $lessons): bool => $lessons->pluck('slug')->all() === [
                'reflected-xss',
                'stored-xss',
            ],
        )
        ->where('learningPath.modules.0.progress', [
            'total' => 1,
            'started' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'remaining' => 1,
            'completion_percentage' => 0,
        ])
        ->where('learningPath.modules.0.is_complete', false)
        ->where('learningPath.modules.0.lessons.0.state', 'not_started')
        ->where('learningPath.modules.0.lessons.0.is_next', true)
        ->where('learningPath.modules.0.lessons.0.lab', [
            'slug' => 'sql-injection-authentication-bypass',
            'title' => 'SQL Injection Authentication Bypass',
        ])
        ->where(
            'learningPath.modules',
            fn (Collection $modules): bool => $modules
                ->flatMap(fn (array $module): array => $module['lessons'])
                ->every(fn (array $lesson): bool => $lesson['state'] === 'not_started'),
        )
        ->missing('learningPath.modules.0.lessons.0.content')
        ->missing('learningPath.modules.0.lessons.0.lab.scenario'),
    );
});

test('detail progress and lesson states are isolated to the current user', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $completedLesson = Lesson::query()
        ->where('slug', 'sql-injection-authentication-bypass')
        ->firstOrFail();
    $startedLesson = Lesson::query()->where('slug', 'reflected-xss')->firstOrFail();

    LessonProgress::factory()->for($user)->for($completedLesson)->completed()->create();
    LessonProgress::factory()->for($user)->for($startedLesson)->started()->create();
    Lesson::query()->get()->each(function (Lesson $lesson) use ($otherUser): void {
        LessonProgress::factory()->for($otherUser)->for($lesson)->completed()->create();
    });

    $response = $this->actingAs($user)->get(route('learning-paths.show', [
        'learningPath' => 'web-security-fundamentals',
    ]));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('learningPath.progress', [
            'total' => 5,
            'started' => 2,
            'in_progress' => 1,
            'completed' => 1,
            'remaining' => 4,
            'completion_percentage' => 20,
        ])
        ->where('learningPath.modules.0.progress', [
            'total' => 1,
            'started' => 1,
            'in_progress' => 0,
            'completed' => 1,
            'remaining' => 0,
            'completion_percentage' => 100,
        ])
        ->where('learningPath.modules.0.is_complete', true)
        ->where('learningPath.modules.0.lessons.0.state', 'completed')
        ->where('learningPath.modules.1.lessons.0.state', 'in_progress')
        ->where('learningPath.modules.1.lessons.0.is_next', true)
        ->where('learningPath.modules.1.lessons.1.state', 'not_started')
        ->where('learningPath.next_lesson', [
            'slug' => 'reflected-xss',
            'title' => 'Reflected XSS',
        ]),
    );
});

test('completed paths expose no next lesson and complete modules', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::factory()->create();
    Lesson::query()->get()->each(function (Lesson $lesson) use ($user): void {
        LessonProgress::factory()->for($user)->for($lesson)->completed()->create();
    });

    $response = $this->actingAs($user)->get(route('learning-paths.show', [
        'learningPath' => 'web-security-fundamentals',
    ]));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('learningPath.progress', [
            'total' => 5,
            'started' => 5,
            'in_progress' => 0,
            'completed' => 5,
            'remaining' => 0,
            'completion_percentage' => 100,
        ])
        ->where('learningPath.is_complete', true)
        ->where('learningPath.next_lesson', null)
        ->where(
            'learningPath.modules',
            fn (Collection $modules): bool => $modules->every(
                fn (array $module): bool => $module['is_complete'] === true,
            ),
        ),
    );
});

test('detail uses explicit curriculum positions and supports an empty module', function () {
    $user = User::factory()->create();
    $learningPath = LearningPath::factory()->create();
    $laterModule = LearningModule::factory()->for($learningPath)->create([
        'title' => 'Later module',
        'position' => 20,
    ]);
    Lesson::factory()->for($laterModule)->create([
        'title' => 'Later lesson',
        'position' => 20,
    ]);
    Lesson::factory()->for($laterModule)->create([
        'title' => 'Earlier lesson',
        'position' => 10,
    ]);
    LearningModule::factory()->for($learningPath)->create([
        'title' => 'Empty first module',
        'position' => 10,
    ]);

    $response = $this->actingAs($user)->get(route('learning-paths.show', [
        'learningPath' => $learningPath->slug,
    ]));

    $response->assertInertia(fn (Assert $page) => $page
        ->where(
            'learningPath.modules',
            fn (Collection $modules): bool => $modules->pluck('position')->all() === [10, 20],
        )
        ->has('learningPath.modules.0.lessons', 0)
        ->where('learningPath.modules.0.progress', [
            'total' => 0,
            'started' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'remaining' => 0,
            'completion_percentage' => 0,
        ])
        ->where('learningPath.modules.0.is_complete', false)
        ->where(
            'learningPath.modules.1.lessons',
            fn (Collection $lessons): bool => $lessons->pluck('position')->all() === [10, 20],
        )
        ->where('learningPath.next_lesson.title', 'Earlier lesson'),
    );
});

test('detail safely renders a learning path without modules', function () {
    $user = User::factory()->create();
    $learningPath = LearningPath::factory()->create();

    $response = $this->actingAs($user)->get(route('learning-paths.show', [
        'learningPath' => $learningPath->slug,
    ]));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('learning-path-detail')
        ->where('learningPath.module_count', 0)
        ->where('learningPath.lesson_count', 0)
        ->where('learningPath.progress', [
            'total' => 0,
            'started' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'remaining' => 0,
            'completion_percentage' => 0,
        ])
        ->where('learningPath.is_complete', false)
        ->where('learningPath.next_lesson', null)
        ->has('learningPath.modules', 0),
    );
});

test('unknown learning path slugs return not found', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('learning-paths.show', [
        'learningPath' => 'missing-learning-path',
    ]));

    $response->assertNotFound();
});
