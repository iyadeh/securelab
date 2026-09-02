<?php

use App\Models\LearningModule;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use App\Services\LearningProgressService;

describe('starting lessons', function () {
    test('guests are redirected when starting a lesson', function () {
        $learningPath = LearningPath::factory()->create();
        $learningModule = LearningModule::factory()->for($learningPath)->create();
        $lesson = Lesson::factory()->for($learningModule)->create();

        $response = $this->post(route('lessons.start', [
            'learningPath' => $learningPath->slug,
            'lesson' => $lesson->slug,
        ]));

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('lesson_progress', 0);
    });

    test('unverified users are redirected when starting a lesson', function () {
        $user = User::factory()->unverified()->create();
        $learningPath = LearningPath::factory()->create();
        $learningModule = LearningModule::factory()->for($learningPath)->create();
        $lesson = Lesson::factory()->for($learningModule)->create();

        $response = $this->actingAs($user)->post(route('lessons.start', [
            'learningPath' => $learningPath->slug,
            'lesson' => $lesson->slug,
        ]));

        $response->assertRedirect(route('verification.notice'));
        $this->assertDatabaseCount('lesson_progress', 0);
    });

    test('verified users start their own lesson with server timestamps', function () {
        $this->travelTo('2026-09-02 10:00:00');
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $learningPath = LearningPath::factory()->create();
        $learningModule = LearningModule::factory()->for($learningPath)->create();
        $lesson = Lesson::factory()->for($learningModule)->create();
        $lessonUrl = route('lessons.show', [
            'learningPath' => $learningPath->slug,
            'lesson' => $lesson->slug,
        ]);

        $response = $this->actingAs($user)->post(route('lessons.start', [
            'learningPath' => $learningPath->slug,
            'lesson' => $lesson->slug,
        ]), [
            'user_id' => $otherUser->id,
            'started_at' => '2000-01-01 00:00:00',
            'completed_at' => '2000-01-01 00:00:00',
            'state' => 'completed',
        ]);

        $response->assertRedirect($lessonUrl);
        $this->assertDatabaseCount('lesson_progress', 1);
        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'started_at' => '2026-09-02 10:00:00',
            'completed_at' => null,
        ]);
        expect((new LearningProgressService)->lessonState($user, $lesson))->toBe('in_progress');
    });

    test('starting twice preserves the original start timestamp', function () {
        $user = User::factory()->create();
        $learningPath = LearningPath::factory()->create();
        $learningModule = LearningModule::factory()->for($learningPath)->create();
        $lesson = Lesson::factory()->for($learningModule)->create();
        $route = route('lessons.start', [
            'learningPath' => $learningPath->slug,
            'lesson' => $lesson->slug,
        ]);
        $this->travelTo('2026-09-02 10:00:00');
        $this->actingAs($user)->post($route)->assertRedirect();
        $progress = LessonProgress::query()->firstOrFail();

        $this->travelTo('2026-09-02 11:00:00');
        $response = $this->actingAs($user)->post($route);

        $response->assertRedirect();
        $this->assertDatabaseCount('lesson_progress', 1);
        expect($progress->fresh()?->started_at->toDateTimeString())->toBe('2026-09-02 10:00:00')
            ->and($progress->fresh()?->completed_at)->toBeNull();
    });

    test('starting a completed lesson does not regress its timestamps', function () {
        $user = User::factory()->create();
        $learningPath = LearningPath::factory()->create();
        $learningModule = LearningModule::factory()->for($learningPath)->create();
        $lesson = Lesson::factory()->for($learningModule)->create();
        $progress = LessonProgress::factory()->for($user)->for($lesson)->completed()->create([
            'started_at' => '2026-09-02 09:00:00',
            'completed_at' => '2026-09-02 09:30:00',
        ]);
        $this->travelTo('2026-09-02 12:00:00');

        $response = $this->actingAs($user)->post(route('lessons.start', [
            'learningPath' => $learningPath->slug,
            'lesson' => $lesson->slug,
        ]));

        $response->assertRedirect();
        $this->assertDatabaseCount('lesson_progress', 1);
        expect($progress->fresh()?->started_at->toDateTimeString())->toBe('2026-09-02 09:00:00')
            ->and($progress->fresh()?->completed_at?->toDateTimeString())->toBe('2026-09-02 09:30:00');
    });
});

describe('completing lessons', function () {
    test('completing a new lesson updates domain progress for only the authenticated user', function () {
        $this->travelTo('2026-09-02 10:00:00');
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $learningPath = LearningPath::factory()->create();
        $learningModule = LearningModule::factory()->for($learningPath)->create(['position' => 1]);
        $lesson = Lesson::factory()->for($learningModule)->create(['position' => 1]);
        $nextLesson = Lesson::factory()->for($learningModule)->create(['position' => 2]);
        $otherProgress = LessonProgress::factory()->for($otherUser)->for($lesson)->started()->create([
            'started_at' => '2026-09-02 09:00:00',
        ]);
        $lessonUrl = route('lessons.show', [
            'learningPath' => $learningPath->slug,
            'lesson' => $lesson->slug,
        ]);

        $response = $this->actingAs($user)->post(route('lessons.complete', [
            'learningPath' => $learningPath->slug,
            'lesson' => $lesson->slug,
        ]), [
            'user_id' => $otherUser->id,
            'started_at' => '2000-01-01 00:00:00',
            'completed_at' => '2000-01-01 00:00:00',
            'state' => 'not_started',
        ]);

        $response->assertRedirect($lessonUrl);
        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'started_at' => '2026-09-02 10:00:00',
            'completed_at' => '2026-09-02 10:00:00',
        ]);
        $progressService = new LearningProgressService;
        expect($progressService->lessonState($user, $lesson))->toBe('completed')
            ->and($progressService->learningPathProgress($user, $learningPath))->toBe([
                'total' => 2,
                'started' => 1,
                'in_progress' => 0,
                'completed' => 1,
                'remaining' => 1,
                'completion_percentage' => 50,
            ])
            ->and($progressService->learningModuleProgress($user, $learningModule))->toBe([
                'total' => 2,
                'started' => 1,
                'in_progress' => 0,
                'completed' => 1,
                'remaining' => 1,
            ])
            ->and($progressService->nextIncompleteLesson($user, $learningPath)?->is($nextLesson))->toBeTrue()
            ->and($otherProgress->fresh()?->started_at->toDateTimeString())->toBe('2026-09-02 09:00:00')
            ->and($otherProgress->fresh()?->completed_at)->toBeNull();
    });

    test('completing a started lesson twice preserves both original timestamps', function () {
        $user = User::factory()->create();
        $learningPath = LearningPath::factory()->create();
        $learningModule = LearningModule::factory()->for($learningPath)->create();
        $lesson = Lesson::factory()->for($learningModule)->create();
        $progress = LessonProgress::factory()->for($user)->for($lesson)->started()->create([
            'started_at' => '2026-09-02 09:00:00',
        ]);
        $route = route('lessons.complete', [
            'learningPath' => $learningPath->slug,
            'lesson' => $lesson->slug,
        ]);
        $this->travelTo('2026-09-02 10:00:00');
        $this->actingAs($user)->post($route)->assertRedirect();

        $this->travelTo('2026-09-02 11:00:00');
        $response = $this->actingAs($user)->post($route);

        $response->assertRedirect();
        $this->assertDatabaseCount('lesson_progress', 1);
        expect($progress->fresh()?->started_at->toDateTimeString())->toBe('2026-09-02 09:00:00')
            ->and($progress->fresh()?->completed_at?->toDateTimeString())->toBe('2026-09-02 10:00:00');
    });
});

test('lesson mutation routes return 404 for unknown or mismatched lessons', function (
    string $routeName,
    bool $usesMismatchedPath,
) {
    $user = User::factory()->create();
    $learningPath = LearningPath::factory()->create(['slug' => 'correct-path']);
    $otherLearningPath = LearningPath::factory()->create(['slug' => 'other-path']);
    $learningModule = LearningModule::factory()->for($learningPath)->create();
    $lesson = Lesson::factory()->for($learningModule)->create(['slug' => 'known-lesson']);

    $response = $this->actingAs($user)->post(route($routeName, [
        'learningPath' => $usesMismatchedPath ? $otherLearningPath->slug : $learningPath->slug,
        'lesson' => $usesMismatchedPath ? $lesson->slug : 'missing-lesson',
    ]));

    $response->assertNotFound();
    $this->assertDatabaseCount('lesson_progress', 0);
})->with([
    'start with unknown lesson' => ['lessons.start', false],
    'start with mismatched path' => ['lessons.start', true],
    'complete with unknown lesson' => ['lessons.complete', false],
    'complete with mismatched path' => ['lessons.complete', true],
]);

test('viewing lessons preserves existing progress timestamps', function () {
    $user = User::factory()->create();
    $learningPath = LearningPath::factory()->create();
    $learningModule = LearningModule::factory()->for($learningPath)->create(['position' => 1]);
    $startedLesson = Lesson::factory()->for($learningModule)->create(['position' => 1]);
    $completedLesson = Lesson::factory()->for($learningModule)->create(['position' => 2]);
    $startedProgress = LessonProgress::factory()->for($user)->for($startedLesson)->started()->create([
        'started_at' => '2026-09-02 09:00:00',
    ]);
    $completedProgress = LessonProgress::factory()->for($user)->for($completedLesson)->completed()->create([
        'started_at' => '2026-09-02 08:00:00',
        'completed_at' => '2026-09-02 08:30:00',
    ]);
    $this->travelTo('2026-09-02 12:00:00');

    $this->actingAs($user)->get(route('lessons.show', [
        'learningPath' => $learningPath->slug,
        'lesson' => $startedLesson->slug,
    ]))->assertOk();
    $this->actingAs($user)->get(route('lessons.show', [
        'learningPath' => $learningPath->slug,
        'lesson' => $completedLesson->slug,
    ]))->assertOk();

    $this->assertDatabaseCount('lesson_progress', 2);
    expect($startedProgress->fresh()?->started_at->toDateTimeString())->toBe('2026-09-02 09:00:00')
        ->and($startedProgress->fresh()?->completed_at)->toBeNull()
        ->and($completedProgress->fresh()?->started_at->toDateTimeString())->toBe('2026-09-02 08:00:00')
        ->and($completedProgress->fresh()?->completed_at?->toDateTimeString())->toBe('2026-09-02 08:30:00');
});
