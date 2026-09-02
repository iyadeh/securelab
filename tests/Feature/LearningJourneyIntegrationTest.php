<?php

use App\Models\Lab;
use App\Models\LabProgress;
use App\Models\LearningModule;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use App\Services\LearningProgressService;
use Database\Seeders\DatabaseSeeder;

test('a fresh user can complete the canonical learning journey', function () {
    $this->seed(DatabaseSeeder::class);

    $user = User::factory()->create();
    $service = new LearningProgressService;
    $learningPath = LearningPath::query()
        ->where('slug', 'web-security-fundamentals')
        ->firstOrFail();
    $firstLesson = Lesson::query()
        ->where('slug', 'sql-injection-authentication-bypass')
        ->firstOrFail();
    $secondLesson = Lesson::query()
        ->where('slug', 'reflected-xss')
        ->firstOrFail();
    $firstModule = $firstLesson->learningModule;
    $secondModule = $secondLesson->learningModule;

    expect($user->lessonProgress)->toBeEmpty()
        ->and($user->labProgress)->toBeEmpty();
    expect($service->learningPathProgress($user, $learningPath))->toBe([
        'total' => 5,
        'started' => 0,
        'in_progress' => 0,
        'completed' => 0,
        'remaining' => 5,
        'completion_percentage' => 0,
    ]);
    expect($service->nextIncompleteLesson($user, $learningPath)?->is($firstLesson))->toBeTrue();
    expect($service->labProgress($user))->toBe([
        'total' => 5,
        'started' => 0,
        'in_progress' => 0,
        'completed' => 0,
        'remaining' => 5,
    ]);

    $firstLessonProgress = LessonProgress::factory()
        ->for($user)
        ->for($firstLesson)
        ->started()
        ->create();

    expect($service->lessonState($user, $firstLesson))->toBe('in_progress');
    expect($service->learningPathProgress($user, $learningPath))->toBe([
        'total' => 5,
        'started' => 1,
        'in_progress' => 1,
        'completed' => 0,
        'remaining' => 5,
        'completion_percentage' => 0,
    ]);
    expect($service->nextIncompleteLesson($user, $learningPath)?->is($firstLesson))->toBeTrue();

    $firstLessonProgress->update(['completed_at' => now()]);

    expect($service->lessonState($user, $firstLesson))->toBe('completed');
    expect($service->learningPathProgress($user, $learningPath))->toBe([
        'total' => 5,
        'started' => 1,
        'in_progress' => 0,
        'completed' => 1,
        'remaining' => 4,
        'completion_percentage' => 20,
    ]);
    expect($service->learningModuleProgress($user, $firstModule))->toBe([
        'total' => 1,
        'started' => 1,
        'in_progress' => 0,
        'completed' => 1,
        'remaining' => 0,
    ]);

    $nextLesson = $service->nextIncompleteLesson($user, $learningPath);

    expect($nextLesson?->is($secondLesson))->toBeTrue()
        ->and($nextLesson?->learningModule->is($secondModule))->toBeTrue()
        ->and($secondModule->position)->toBeGreaterThan($firstModule->position);

    $linkedLab = Lab::query()->where('slug', 'reflected-xss')->firstOrFail();
    expect($secondLesson->lab?->is($linkedLab))->toBeTrue();

    $labProgress = LabProgress::factory()
        ->for($user)
        ->for($linkedLab)
        ->started()
        ->create();

    expect($service->labState($user, $linkedLab))->toBe('in_progress');
    expect($service->labProgress($user))->toBe([
        'total' => 5,
        'started' => 1,
        'in_progress' => 1,
        'completed' => 0,
        'remaining' => 5,
    ]);

    $labProgress->update(['completed_at' => now()]);

    expect($service->labState($user, $linkedLab))->toBe('completed')
        ->and($service->lessonState($user, $secondLesson))->toBe('not_started');
    expect($service->labProgress($user))->toBe([
        'total' => 5,
        'started' => 1,
        'in_progress' => 0,
        'completed' => 1,
        'remaining' => 4,
    ]);
    expect($service->learningPathProgress($user, $learningPath))->toBe([
        'total' => 5,
        'started' => 1,
        'in_progress' => 0,
        'completed' => 1,
        'remaining' => 4,
        'completion_percentage' => 20,
    ]);

    Lesson::query()
        ->whereIn('slug', ['reflected-xss', 'stored-xss', 'idor', 'weak-password-reset'])
        ->get()
        ->each(function (Lesson $lesson) use ($user): void {
            LessonProgress::factory()->for($user)->for($lesson)->completed()->create();
        });

    expect($service->learningPathProgress($user, $learningPath))->toBe([
        'total' => 5,
        'started' => 5,
        'in_progress' => 0,
        'completed' => 5,
        'remaining' => 0,
        'completion_percentage' => 100,
    ]);
    expect($service->nextIncompleteLesson($user, $learningPath))->toBeNull();

    $moduleProgress = $learningPath->learningModules()
        ->get()
        ->mapWithKeys(fn (LearningModule $learningModule): array => [
            $learningModule->title => $service->learningModuleProgress($user, $learningModule),
        ])
        ->all();

    expect($moduleProgress)->toBe([
        'Injection' => [
            'total' => 1,
            'started' => 1,
            'in_progress' => 0,
            'completed' => 1,
            'remaining' => 0,
        ],
        'Cross-Site Scripting' => [
            'total' => 2,
            'started' => 2,
            'in_progress' => 0,
            'completed' => 2,
            'remaining' => 0,
        ],
        'Access & Account Security' => [
            'total' => 2,
            'started' => 2,
            'in_progress' => 0,
            'completed' => 2,
            'remaining' => 0,
        ],
    ]);
});

test('canonical progress is isolated between users', function () {
    $this->seed(DatabaseSeeder::class);

    $completedUser = User::factory()->create();
    $freshUser = User::factory()->create();
    $service = new LearningProgressService;
    $learningPath = LearningPath::query()
        ->where('slug', 'web-security-fundamentals')
        ->firstOrFail();
    $firstLesson = Lesson::query()
        ->where('slug', 'sql-injection-authentication-bypass')
        ->firstOrFail();
    $firstLab = Lab::query()
        ->where('slug', 'sql-injection-authentication-bypass')
        ->firstOrFail();

    Lesson::query()->get()->each(function (Lesson $lesson) use ($completedUser): void {
        LessonProgress::factory()->for($completedUser)->for($lesson)->completed()->create();
    });
    Lab::query()->get()->each(function (Lab $lab) use ($completedUser): void {
        LabProgress::factory()->for($completedUser)->for($lab)->completed()->create();
    });

    expect($service->learningPathProgress($completedUser, $learningPath)['completion_percentage'])->toBe(100)
        ->and($service->labProgress($completedUser)['completed'])->toBe(5);
    expect($service->lessonState($freshUser, $firstLesson))->toBe('not_started')
        ->and($service->labState($freshUser, $firstLab))->toBe('not_started');
    expect($service->learningPathProgress($freshUser, $learningPath))->toBe([
        'total' => 5,
        'started' => 0,
        'in_progress' => 0,
        'completed' => 0,
        'remaining' => 5,
        'completion_percentage' => 0,
    ]);
    expect($service->nextIncompleteLesson($freshUser, $learningPath)?->is($firstLesson))->toBeTrue();
    expect($service->labProgress($freshUser))->toBe([
        'total' => 5,
        'started' => 0,
        'in_progress' => 0,
        'completed' => 0,
        'remaining' => 5,
    ]);
});

test('reseed preserves existing canonical progress and relationships', function () {
    $this->seed(DatabaseSeeder::class);

    $user = User::factory()->create();
    $service = new LearningProgressService;
    $learningPath = LearningPath::query()
        ->where('slug', 'web-security-fundamentals')
        ->firstOrFail();
    $lesson = Lesson::query()
        ->where('slug', 'sql-injection-authentication-bypass')
        ->firstOrFail();
    $lab = Lab::query()
        ->where('slug', 'sql-injection-authentication-bypass')
        ->firstOrFail();
    $lessonProgress = LessonProgress::factory()
        ->for($user)
        ->for($lesson)
        ->completed()
        ->create();
    $labProgress = LabProgress::factory()
        ->for($user)
        ->for($lab)
        ->completed()
        ->create();

    $this->seed(DatabaseSeeder::class);

    $this->assertModelExists($lessonProgress);
    $this->assertModelExists($labProgress);
    $lessonProgress->refresh();
    $labProgress->refresh();

    expect(LearningPath::query()->count())->toBe(1)
        ->and(LearningModule::query()->count())->toBe(3)
        ->and(Lesson::query()->count())->toBe(5)
        ->and(Lab::query()->count())->toBe(5)
        ->and($user->lessonProgress()->count())->toBe(1)
        ->and($user->labProgress()->count())->toBe(1);
    expect($lessonProgress->lesson->slug)->toBe('sql-injection-authentication-bypass')
        ->and($lessonProgress->lesson->lab?->slug)->toBe('sql-injection-authentication-bypass')
        ->and($labProgress->lab->slug)->toBe('sql-injection-authentication-bypass');
    expect($service->lessonState($user, $lessonProgress->lesson))->toBe('completed')
        ->and($service->labState($user, $labProgress->lab))->toBe('completed');
    expect($service->learningPathProgress($user, $learningPath))->toBe([
        'total' => 5,
        'started' => 1,
        'in_progress' => 0,
        'completed' => 1,
        'remaining' => 4,
        'completion_percentage' => 20,
    ]);
    expect($service->labProgress($user))->toBe([
        'total' => 5,
        'started' => 1,
        'in_progress' => 0,
        'completed' => 1,
        'remaining' => 4,
    ]);
});
