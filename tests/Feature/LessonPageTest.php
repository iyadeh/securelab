<?php

use App\Models\LearningModule;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from lessons', function () {
    $learningPath = LearningPath::factory()->create();
    $learningModule = LearningModule::factory()->for($learningPath)->create();
    $lesson = Lesson::factory()->for($learningModule)->create();

    $response = $this->get(route('lessons.show', [
        'learningPath' => $learningPath->slug,
        'lesson' => $lesson->slug,
    ]));

    $response->assertRedirect(route('login'));
});

test('unverified users are redirected from lessons', function () {
    $user = User::factory()->unverified()->create();
    $learningPath = LearningPath::factory()->create();
    $learningModule = LearningModule::factory()->for($learningPath)->create();
    $lesson = Lesson::factory()->for($learningModule)->create();

    $response = $this->actingAs($user)->get(route('lessons.show', [
        'learningPath' => $learningPath->slug,
        'lesson' => $lesson->slug,
    ]));

    $response->assertRedirect(route('verification.notice'));
});

test('verified users can read canonical lesson content without mutating progress', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('lessons.show', [
        'learningPath' => 'web-security-fundamentals',
        'lesson' => 'sql-injection-authentication-bypass',
    ]));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('lesson')
        ->where('learningPath', [
            'slug' => 'web-security-fundamentals',
            'title' => 'Web Security Fundamentals',
        ])
        ->where('module', [
            'title' => 'Injection',
            'position' => 1,
        ])
        ->whereAll([
            'lesson.slug' => 'sql-injection-authentication-bypass',
            'lesson.title' => 'SQL Injection Authentication Bypass',
            'lesson.summary' => 'Understand how unsafe query construction can turn login input into an authentication bypass.',
            'lesson.estimated_minutes' => 20,
            'lesson.position' => 1,
            'lesson.state' => 'not_started',
        ])
        ->where(
            'lesson.content',
            fn (string $content): bool => collect(['Learn', 'Understand', 'Exploit', 'Analyze', 'Fix'])
                ->every(fn (string $stage): bool => str_contains($content, "## {$stage}")),
        )
        ->where('lab', [
            'slug' => 'sql-injection-authentication-bypass',
            'title' => 'SQL Injection Authentication Bypass',
            'difficulty' => 'easy',
            'vulnerability_type' => 'sql_injection',
        ])
        ->missing('lab.scenario')
        ->where('previousLesson', null)
        ->where('nextLesson', [
            'slug' => 'reflected-xss',
            'title' => 'Reflected XSS',
        ]),
    );
    $this->assertDatabaseCount('lesson_progress', 0);
});

test('unknown and path-mismatched lesson slugs return not found', function (string $lessonSlug) {
    $this->seed(DatabaseSeeder::class);
    $user = User::factory()->create();
    $otherPath = LearningPath::factory()->create(['slug' => 'another-path']);

    $learningPathSlug = $lessonSlug === 'missing-lesson'
        ? 'web-security-fundamentals'
        : $otherPath->slug;

    $response = $this->actingAs($user)->get(route('lessons.show', [
        'learningPath' => $learningPathSlug,
        'lesson' => $lessonSlug,
    ]));

    $response->assertNotFound();
})->with([
    'unknown lesson' => 'missing-lesson',
    'lesson from another path' => 'sql-injection-authentication-bypass',
]);

test('lesson state belongs only to the current user', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $lesson = Lesson::query()->where('slug', 'reflected-xss')->firstOrFail();
    LessonProgress::factory()->for($user)->for($lesson)->started()->create();
    LessonProgress::factory()->for($otherUser)->for($lesson)->completed()->create();

    $route = route('lessons.show', [
        'learningPath' => 'web-security-fundamentals',
        'lesson' => $lesson->slug,
    ]);

    $this->actingAs($user)->get($route)->assertInertia(
        fn (Assert $page) => $page->where('lesson.state', 'in_progress'),
    );

    $user->lessonProgress()->whereBelongsTo($lesson, 'lesson')->update([
        'completed_at' => now(),
    ]);

    $this->actingAs($user)->get($route)->assertInertia(
        fn (Assert $page) => $page->where('lesson.state', 'completed'),
    );
});

test('lessons without labs and single-lesson paths render safely', function () {
    $user = User::factory()->create();
    $learningPath = LearningPath::factory()->create();
    $learningModule = LearningModule::factory()->for($learningPath)->create([
        'title' => 'Single module',
        'position' => 1,
    ]);
    $lesson = Lesson::factory()->for($learningModule)->create([
        'lab_id' => null,
        'position' => 1,
    ]);

    $response = $this->actingAs($user)->get(route('lessons.show', [
        'learningPath' => $learningPath->slug,
        'lesson' => $lesson->slug,
    ]));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('lesson')
        ->where('lab', null)
        ->where('previousLesson', null)
        ->where('nextLesson', null),
    );
});

test('last canonical lesson has correct previous lesson and no next lesson', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('lessons.show', [
        'learningPath' => 'web-security-fundamentals',
        'lesson' => 'weak-password-reset',
    ]));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('previousLesson', [
            'slug' => 'idor',
            'title' => 'IDOR',
        ])
        ->where('nextLesson', null),
    );
});

test('curriculum navigation uses explicit positions across module boundaries', function () {
    $user = User::factory()->create();
    $learningPath = LearningPath::factory()->create();
    $laterModule = LearningModule::factory()->for($learningPath)->create([
        'title' => 'Later module',
        'position' => 20,
    ]);
    $lastLesson = Lesson::factory()->for($laterModule)->create([
        'slug' => 'last-by-position',
        'title' => 'Last by position',
        'position' => 20,
    ]);
    $middleLesson = Lesson::factory()->for($laterModule)->create([
        'slug' => 'middle-by-position',
        'title' => 'Middle by position',
        'position' => 10,
    ]);
    $earlierModule = LearningModule::factory()->for($learningPath)->create([
        'title' => 'Earlier module',
        'position' => 10,
    ]);
    $firstLesson = Lesson::factory()->for($earlierModule)->create([
        'slug' => 'first-by-position',
        'title' => 'First by position',
        'position' => 10,
    ]);

    expect($lastLesson->id)->toBeLessThan($middleLesson->id)
        ->and($laterModule->id)->toBeLessThan($earlierModule->id)
        ->and($middleLesson->id)->toBeLessThan($firstLesson->id);

    $response = $this->actingAs($user)->get(route('lessons.show', [
        'learningPath' => $learningPath->slug,
        'lesson' => $middleLesson->slug,
    ]));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('module', [
            'title' => 'Later module',
            'position' => 20,
        ])
        ->where('previousLesson', [
            'slug' => $firstLesson->slug,
            'title' => $firstLesson->title,
        ])
        ->where('nextLesson', [
            'slug' => $lastLesson->slug,
            'title' => $lastLesson->title,
        ]),
    );
});
