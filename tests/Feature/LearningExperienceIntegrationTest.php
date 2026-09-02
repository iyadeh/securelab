<?php

use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

const LEARNING_EXPERIENCE_PATH_SLUG = 'web-security-fundamentals';

const LEARNING_EXPERIENCE_LESSON_SLUGS = [
    'sql-injection-authentication-bypass',
    'reflected-xss',
    'stored-xss',
    'idor',
    'weak-password-reset',
];

function learningExperienceLessonRoute(string $routeName, string $lessonSlug): string
{
    return route($routeName, [
        'learningPath' => LEARNING_EXPERIENCE_PATH_SLUG,
        'lesson' => $lessonSlug,
    ]);
}

function completeLearningExperienceLesson(TestCase $testCase, User $user, string $lessonSlug): void
{
    $testCase->actingAs($user)
        ->post(learningExperienceLessonRoute('lessons.start', $lessonSlug))
        ->assertRedirect(learningExperienceLessonRoute('lessons.show', $lessonSlug));
    $testCase->actingAs($user)
        ->post(learningExperienceLessonRoute('lessons.complete', $lessonSlug))
        ->assertRedirect(learningExperienceLessonRoute('lessons.show', $lessonSlug));
}

test('fresh users see one read-only canonical journey across every learning page', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::factory()->create();

    $overviewResponse = $this->actingAs($user)->get(route('learn'));
    $pathResponse = $this->actingAs($user)->get(route('learning-paths.show', [
        'learningPath' => LEARNING_EXPERIENCE_PATH_SLUG,
    ]));
    $lessonResponse = $this->actingAs($user)->get(learningExperienceLessonRoute(
        'lessons.show',
        LEARNING_EXPERIENCE_LESSON_SLUGS[0],
    ));

    $overviewResponse->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('learning-paths')
        ->whereAll([
            'learningPaths.0.slug' => LEARNING_EXPERIENCE_PATH_SLUG,
            'learningPaths.0.completed_lessons' => 0,
            'learningPaths.0.completion_percentage' => 0,
            'learningPaths.0.is_complete' => false,
            'learningPaths.0.next_lesson.title' => 'SQL Injection Authentication Bypass',
        ]),
    );
    $pathResponse->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('learning-path-detail')
        ->where('learningPath.progress', [
            'total' => 5,
            'started' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'remaining' => 5,
            'completion_percentage' => 0,
        ])
        ->where('learningPath.next_lesson.slug', LEARNING_EXPERIENCE_LESSON_SLUGS[0])
        ->where('learningPath.modules.0.lessons.0.is_next', true)
        ->where(
            'learningPath.modules',
            fn (Collection $modules): bool => $modules
                ->flatMap(fn (array $module): array => $module['lessons'])
                ->every(fn (array $lesson): bool => $lesson['state'] === 'not_started'),
        ),
    );
    $lessonResponse->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('lesson')
        ->where('lesson.state', 'not_started')
        ->where('previousLesson', null)
        ->where('nextLesson.slug', LEARNING_EXPERIENCE_LESSON_SLUGS[1]),
    );
    $this->assertDatabaseCount('lesson_progress', 0);
});

test('starting the first lesson keeps all learning pages on the same in-progress state', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::factory()->create();
    $firstLesson = Lesson::query()
        ->where('slug', LEARNING_EXPERIENCE_LESSON_SLUGS[0])
        ->firstOrFail();
    $this->travelTo('2026-09-02 09:00:00');

    $response = $this->actingAs($user)->post(learningExperienceLessonRoute(
        'lessons.start',
        $firstLesson->slug,
    ));

    $response->assertRedirect(learningExperienceLessonRoute('lessons.show', $firstLesson->slug));
    $this->assertDatabaseCount('lesson_progress', 1);
    $this->assertDatabaseHas('lesson_progress', [
        'user_id' => $user->id,
        'lesson_id' => $firstLesson->id,
        'started_at' => '2026-09-02 09:00:00',
        'completed_at' => null,
    ]);
    $this->actingAs($user)
        ->get(learningExperienceLessonRoute('lessons.show', $firstLesson->slug))
        ->assertInertia(fn (Assert $page) => $page->where('lesson.state', 'in_progress'));
    $this->actingAs($user)
        ->get(route('learning-paths.show', ['learningPath' => LEARNING_EXPERIENCE_PATH_SLUG]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('learningPath.progress', [
                'total' => 5,
                'started' => 1,
                'in_progress' => 1,
                'completed' => 0,
                'remaining' => 5,
                'completion_percentage' => 0,
            ])
            ->where('learningPath.modules.0.progress.completed', 0)
            ->where('learningPath.modules.0.progress.completion_percentage', 0)
            ->where('learningPath.modules.0.lessons.0.state', 'in_progress')
            ->where('learningPath.modules.0.lessons.0.is_next', true)
            ->where('learningPath.next_lesson.slug', $firstLesson->slug),
        );
    $this->actingAs($user)
        ->get(route('learn'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('learningPaths.0.completed_lessons', 0)
            ->where('learningPaths.0.completion_percentage', 0)
            ->where('learningPaths.0.next_lesson.title', $firstLesson->title),
        );
});

test('completing the first lesson advances derived progress across every learning page', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::factory()->create();
    $firstLesson = Lesson::query()
        ->where('slug', LEARNING_EXPERIENCE_LESSON_SLUGS[0])
        ->firstOrFail();
    $this->travelTo('2026-09-02 09:00:00');
    $this->actingAs($user)
        ->post(learningExperienceLessonRoute('lessons.start', $firstLesson->slug))
        ->assertRedirect();
    $this->travelTo('2026-09-02 10:00:00');

    $response = $this->actingAs($user)->post(learningExperienceLessonRoute(
        'lessons.complete',
        $firstLesson->slug,
    ));

    $response->assertRedirect(learningExperienceLessonRoute('lessons.show', $firstLesson->slug));
    $this->assertDatabaseCount('lesson_progress', 1);
    $this->assertDatabaseHas('lesson_progress', [
        'user_id' => $user->id,
        'lesson_id' => $firstLesson->id,
        'started_at' => '2026-09-02 09:00:00',
        'completed_at' => '2026-09-02 10:00:00',
    ]);
    $this->actingAs($user)
        ->get(route('learn'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('learningPaths.0.completed_lessons', 1)
            ->where('learningPaths.0.completion_percentage', 20)
            ->where('learningPaths.0.is_complete', false)
            ->where('learningPaths.0.next_lesson.title', 'Reflected XSS'),
        );
    $this->actingAs($user)
        ->get(route('learning-paths.show', ['learningPath' => LEARNING_EXPERIENCE_PATH_SLUG]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('learningPath.progress', [
                'total' => 5,
                'started' => 1,
                'in_progress' => 0,
                'completed' => 1,
                'remaining' => 4,
                'completion_percentage' => 20,
            ])
            ->where('learningPath.modules.0.is_complete', true)
            ->where('learningPath.modules.0.lessons.0.state', 'completed')
            ->where('learningPath.next_lesson.slug', LEARNING_EXPERIENCE_LESSON_SLUGS[1]),
        );
    $this->actingAs($user)
        ->get(learningExperienceLessonRoute('lessons.show', $firstLesson->slug))
        ->assertInertia(fn (Assert $page) => $page
            ->where('lesson.state', 'completed')
            ->where('previousLesson', null)
            ->where('nextLesson.slug', LEARNING_EXPERIENCE_LESSON_SLUGS[1]),
        );
});

test('supported lesson actions advance through module boundaries in explicit curriculum order', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::factory()->create();

    foreach (array_slice(LEARNING_EXPERIENCE_LESSON_SLUGS, 0, 3) as $lessonSlug) {
        completeLearningExperienceLesson($this, $user, $lessonSlug);
    }

    $this->assertDatabaseCount('lesson_progress', 3);
    $this->actingAs($user)
        ->get(route('learning-paths.show', ['learningPath' => LEARNING_EXPERIENCE_PATH_SLUG]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('learningPath.progress', [
                'total' => 5,
                'started' => 3,
                'in_progress' => 0,
                'completed' => 3,
                'remaining' => 2,
                'completion_percentage' => 60,
            ])
            ->where(
                'learningPath.modules',
                fn (Collection $modules): bool => $modules->pluck('position')->all() === [1, 2, 3],
            )
            ->where('learningPath.modules.0.is_complete', true)
            ->where('learningPath.modules.1.is_complete', true)
            ->where('learningPath.modules.2.is_complete', false)
            ->where('learningPath.modules.2.lessons.0.slug', LEARNING_EXPERIENCE_LESSON_SLUGS[3])
            ->where('learningPath.modules.2.lessons.0.is_next', true)
            ->where('learningPath.next_lesson.slug', LEARNING_EXPERIENCE_LESSON_SLUGS[3]),
        );
    $this->actingAs($user)
        ->get(learningExperienceLessonRoute('lessons.show', LEARNING_EXPERIENCE_LESSON_SLUGS[2]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('lesson.state', 'completed')
            ->where('previousLesson.slug', LEARNING_EXPERIENCE_LESSON_SLUGS[1])
            ->where('nextLesson.slug', LEARNING_EXPERIENCE_LESSON_SLUGS[3]),
        );
});

test('supported lesson actions complete the canonical path without inventing a next lesson', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::factory()->create();

    foreach (LEARNING_EXPERIENCE_LESSON_SLUGS as $lessonSlug) {
        completeLearningExperienceLesson($this, $user, $lessonSlug);
    }

    $this->assertDatabaseCount('lesson_progress', 5);
    $this->actingAs($user)
        ->get(route('learn'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('learningPaths.0.completed_lessons', 5)
            ->where('learningPaths.0.completion_percentage', 100)
            ->where('learningPaths.0.is_complete', true)
            ->where('learningPaths.0.next_lesson', null),
        );
    $this->actingAs($user)
        ->get(route('learning-paths.show', ['learningPath' => LEARNING_EXPERIENCE_PATH_SLUG]))
        ->assertInertia(fn (Assert $page) => $page
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
                    fn (array $module): bool => $module['is_complete'] === true
                        && collect($module['lessons'])->every(
                            fn (array $lesson): bool => $lesson['state'] === 'completed',
                        ),
                ),
            ),
        );
    $this->actingAs($user)
        ->get(learningExperienceLessonRoute('lessons.show', LEARNING_EXPERIENCE_LESSON_SLUGS[4]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('lesson.state', 'completed')
            ->where('previousLesson.slug', LEARNING_EXPERIENCE_LESSON_SLUGS[3])
            ->where('nextLesson', null),
        );
});

test('progress stays isolated while repeated GET navigation remains read-only', function () {
    $this->seed(DatabaseSeeder::class);
    $progressedUser = User::factory()->create();
    $freshUser = User::factory()->create();
    $this->travelTo('2026-09-02 09:00:00');
    completeLearningExperienceLesson($this, $progressedUser, LEARNING_EXPERIENCE_LESSON_SLUGS[0]);
    $this->travelTo('2026-09-02 10:00:00');
    completeLearningExperienceLesson($this, $progressedUser, LEARNING_EXPERIENCE_LESSON_SLUGS[1]);
    $progressSnapshot = fn (): array => $progressedUser->lessonProgress()
        ->with('lesson:id,slug')
        ->get()
        ->mapWithKeys(fn (LessonProgress $progress): array => [
            $progress->lesson->slug => [
                $progress->started_at->toDateTimeString(),
                $progress->completed_at?->toDateTimeString(),
            ],
        ])
        ->sortKeys()
        ->all();
    $originalTimestamps = $progressSnapshot();
    $this->travelTo('2026-09-02 12:00:00');

    $overviewResponse = $this->actingAs($freshUser)->get(route('learn'));
    $pathResponse = $this->actingAs($freshUser)->get(route('learning-paths.show', [
        'learningPath' => LEARNING_EXPERIENCE_PATH_SLUG,
    ]));
    foreach (array_slice(LEARNING_EXPERIENCE_LESSON_SLUGS, 0, 3) as $lessonSlug) {
        $this->actingAs($freshUser)
            ->get(learningExperienceLessonRoute('lessons.show', $lessonSlug))
            ->assertOk();
    }
    $this->actingAs($freshUser)->get(route('learn'))->assertOk();
    $this->actingAs($freshUser)->get(route('learning-paths.show', [
        'learningPath' => LEARNING_EXPERIENCE_PATH_SLUG,
    ]))->assertOk();

    $this->assertDatabaseCount('lesson_progress', 2);
    $this->assertDatabaseMissing('lesson_progress', ['user_id' => $freshUser->id]);
    expect($progressSnapshot())->toBe($originalTimestamps);
    $overviewResponse->assertInertia(fn (Assert $page) => $page
        ->where('learningPaths.0.completed_lessons', 0)
        ->where('learningPaths.0.completion_percentage', 0)
        ->where('learningPaths.0.next_lesson.title', 'SQL Injection Authentication Bypass'),
    );
    $pathResponse->assertInertia(fn (Assert $page) => $page
        ->where('learningPath.progress.completed', 0)
        ->where('learningPath.progress.completion_percentage', 0)
        ->where('learningPath.next_lesson.slug', LEARNING_EXPERIENCE_LESSON_SLUGS[0])
        ->where(
            'learningPath.modules',
            fn (Collection $modules): bool => $modules
                ->flatMap(fn (array $module): array => $module['lessons'])
                ->every(fn (array $lesson): bool => $lesson['state'] === 'not_started'),
        ),
    );
    $this->actingAs($progressedUser)
        ->get(route('learn'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('learningPaths.0.completed_lessons', 2)
            ->where('learningPaths.0.completion_percentage', 40)
            ->where('learningPaths.0.next_lesson.title', 'Stored XSS'),
        );
});

test('repeated lesson actions preserve one progress row and stable timestamps', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::factory()->create();
    $lesson = Lesson::query()
        ->where('slug', LEARNING_EXPERIENCE_LESSON_SLUGS[0])
        ->firstOrFail();
    $startRoute = learningExperienceLessonRoute('lessons.start', $lesson->slug);
    $completeRoute = learningExperienceLessonRoute('lessons.complete', $lesson->slug);
    $this->travelTo('2026-09-02 09:00:00');
    $this->actingAs($user)->post($startRoute)->assertRedirect();
    $this->travelTo('2026-09-02 10:00:00');
    $this->actingAs($user)->post($startRoute)->assertRedirect();
    $this->travelTo('2026-09-02 11:00:00');
    $this->actingAs($user)->post($completeRoute)->assertRedirect();
    $this->travelTo('2026-09-02 12:00:00');

    $response = $this->actingAs($user)->post($completeRoute);

    $response->assertRedirect(learningExperienceLessonRoute('lessons.show', $lesson->slug));
    $this->assertDatabaseCount('lesson_progress', 1);
    $this->assertDatabaseHas('lesson_progress', [
        'user_id' => $user->id,
        'lesson_id' => $lesson->id,
        'started_at' => '2026-09-02 09:00:00',
        'completed_at' => '2026-09-02 11:00:00',
    ]);
    $this->actingAs($user)
        ->get(route('learning-paths.show', ['learningPath' => LEARNING_EXPERIENCE_PATH_SLUG]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('learningPath.progress.started', 1)
            ->where('learningPath.progress.completed', 1)
            ->where('learningPath.progress.completion_percentage', 20),
        );
});

test('unrelated learning paths cannot access or mutate canonical lessons', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::factory()->create();
    $unrelatedPath = LearningPath::factory()->create(['slug' => 'unrelated-path']);
    $routeParameters = [
        'learningPath' => $unrelatedPath->slug,
        'lesson' => LEARNING_EXPERIENCE_LESSON_SLUGS[0],
    ];

    $readResponse = $this->actingAs($user)->get(route('lessons.show', $routeParameters));
    $writeResponse = $this->actingAs($user)->post(route('lessons.complete', $routeParameters));

    $readResponse->assertNotFound();
    $writeResponse->assertNotFound();
    $this->assertDatabaseCount('lesson_progress', 0);
});
