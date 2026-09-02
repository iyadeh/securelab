<?php

use App\Models\Lab;
use App\Models\LabProgress;
use App\Models\LearningModule;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use App\Services\LearningProgressService;

describe('learning path progress', function () {
    test('returns zero progress for a path without lessons', function () {
        $user = User::factory()->create();
        $learningPath = LearningPath::factory()->create();

        $progress = (new LearningProgressService)->learningPathProgress($user, $learningPath);

        expect($progress)->toBe([
            'total' => 0,
            'started' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'remaining' => 0,
            'completion_percentage' => 0,
        ]);
    });

    test('returns zero completed lessons when no lessons have been started', function () {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $learningPath = LearningPath::factory()->create();
        $learningModule = LearningModule::factory()->for($learningPath)->create(['position' => 1]);
        $lessons = Lesson::factory()
            ->count(3)
            ->for($learningModule)
            ->sequence(['position' => 1], ['position' => 2], ['position' => 3])
            ->create();
        LessonProgress::factory()->for($otherUser)->for($lessons[0])->completed()->create();

        $progress = (new LearningProgressService)->learningPathProgress($user, $learningPath);

        expect($progress)->toBe([
            'total' => 3,
            'started' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'remaining' => 3,
            'completion_percentage' => 0,
        ]);
    });

    test('counts partial progress and rounds percentage to the nearest whole number', function () {
        $user = User::factory()->create();
        $learningPath = LearningPath::factory()->create();
        $learningModule = LearningModule::factory()->for($learningPath)->create(['position' => 1]);
        $lessons = Lesson::factory()
            ->count(3)
            ->for($learningModule)
            ->sequence(['position' => 1], ['position' => 2], ['position' => 3])
            ->create();
        LessonProgress::factory()->for($user)->for($lessons[0])->completed()->create();
        LessonProgress::factory()->for($user)->for($lessons[1])->completed()->create();
        LessonProgress::factory()->for($user)->for($lessons[2])->started()->create();

        $progress = (new LearningProgressService)->learningPathProgress($user, $learningPath);

        expect($progress)->toBe([
            'total' => 3,
            'started' => 3,
            'in_progress' => 1,
            'completed' => 2,
            'remaining' => 1,
            'completion_percentage' => 67,
        ]);
    });

    test('returns one hundred percent when every lesson is completed', function () {
        $user = User::factory()->create();
        $learningPath = LearningPath::factory()->create();
        $learningModule = LearningModule::factory()->for($learningPath)->create(['position' => 1]);
        $lessons = Lesson::factory()
            ->count(2)
            ->for($learningModule)
            ->sequence(['position' => 1], ['position' => 2])
            ->create();
        $lessons->each(function (Lesson $lesson) use ($user): void {
            LessonProgress::factory()->for($user)->for($lesson)->completed()->create();
        });

        $progress = (new LearningProgressService)->learningPathProgress($user, $learningPath);

        expect($progress)->toBe([
            'total' => 2,
            'started' => 2,
            'in_progress' => 0,
            'completed' => 2,
            'remaining' => 0,
            'completion_percentage' => 100,
        ]);
    });
});

describe('learning module progress', function () {
    test('returns zero progress when no module lessons have been started', function () {
        $user = User::factory()->create();
        $learningModule = LearningModule::factory()->create();
        Lesson::factory()
            ->count(2)
            ->for($learningModule)
            ->sequence(['position' => 1], ['position' => 2])
            ->create();

        $progress = (new LearningProgressService)->learningModuleProgress($user, $learningModule);

        expect($progress)->toBe([
            'total' => 2,
            'started' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'remaining' => 2,
        ]);
    });

    test('counts partial module progress', function () {
        $user = User::factory()->create();
        $learningModule = LearningModule::factory()->create();
        $lessons = Lesson::factory()
            ->count(3)
            ->for($learningModule)
            ->sequence(['position' => 1], ['position' => 2], ['position' => 3])
            ->create();
        LessonProgress::factory()->for($user)->for($lessons[0])->completed()->create();
        LessonProgress::factory()->for($user)->for($lessons[1])->started()->create();

        $progress = (new LearningProgressService)->learningModuleProgress($user, $learningModule);

        expect($progress)->toBe([
            'total' => 3,
            'started' => 2,
            'in_progress' => 1,
            'completed' => 1,
            'remaining' => 2,
        ]);
    });

    test('returns no remaining lessons for a completed module', function () {
        $user = User::factory()->create();
        $learningModule = LearningModule::factory()->create();
        $lessons = Lesson::factory()
            ->count(2)
            ->for($learningModule)
            ->sequence(['position' => 1], ['position' => 2])
            ->create();
        $lessons->each(function (Lesson $lesson) use ($user): void {
            LessonProgress::factory()->for($user)->for($lesson)->completed()->create();
        });

        $progress = (new LearningProgressService)->learningModuleProgress($user, $learningModule);

        expect($progress)->toBe([
            'total' => 2,
            'started' => 2,
            'in_progress' => 0,
            'completed' => 2,
            'remaining' => 0,
        ]);
    });
});

describe('lesson and lab states', function () {
    test('returns not started for a lesson without this user progress', function () {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $lesson = Lesson::factory()->create();
        LessonProgress::factory()->for($otherUser)->for($lesson)->completed()->create();

        $state = (new LearningProgressService)->lessonState($user, $lesson);

        expect($state)->toBe('not_started');
    });

    test('returns in progress for a started lesson', function () {
        $user = User::factory()->create();
        $lesson = Lesson::factory()->create();
        LessonProgress::factory()->for($user)->for($lesson)->started()->create();

        $state = (new LearningProgressService)->lessonState($user, $lesson);

        expect($state)->toBe('in_progress');
    });

    test('returns completed for a completed lesson', function () {
        $user = User::factory()->create();
        $lesson = Lesson::factory()->create();
        LessonProgress::factory()->for($user)->for($lesson)->completed()->create();

        $state = (new LearningProgressService)->lessonState($user, $lesson);

        expect($state)->toBe('completed');
    });

    test('returns not started for a lab without this user progress', function () {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $lab = Lab::factory()->create();
        LabProgress::factory()->for($otherUser)->for($lab)->completed()->create();

        $state = (new LearningProgressService)->labState($user, $lab);

        expect($state)->toBe('not_started');
    });

    test('returns in progress for a started lab', function () {
        $user = User::factory()->create();
        $lab = Lab::factory()->create();
        LabProgress::factory()->for($user)->for($lab)->started()->create();

        $state = (new LearningProgressService)->labState($user, $lab);

        expect($state)->toBe('in_progress');
    });

    test('returns completed for a completed lab', function () {
        $user = User::factory()->create();
        $lab = Lab::factory()->create();
        LabProgress::factory()->for($user)->for($lab)->completed()->create();

        $state = (new LearningProgressService)->labState($user, $lab);

        expect($state)->toBe('completed');
    });
});

describe('next incomplete lesson', function () {
    test('returns the first lesson by explicit module and lesson positions instead of ids', function () {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $learningPath = LearningPath::factory()->create();
        $laterModule = LearningModule::factory()->for($learningPath)->create(['position' => 20]);
        Lesson::factory()->for($laterModule)->create(['position' => 1]);
        $firstModule = LearningModule::factory()->for($learningPath)->create(['position' => 10]);
        Lesson::factory()->for($firstModule)->create(['position' => 20]);
        $firstLesson = Lesson::factory()->for($firstModule)->create(['position' => 10]);
        LessonProgress::factory()->for($otherUser)->for($firstLesson)->completed()->create();

        $nextLesson = (new LearningProgressService)->nextIncompleteLesson($user, $learningPath);

        expect($nextLesson?->is($firstLesson))->toBeTrue();
    });

    test('keeps a started but incomplete lesson next after skipping completed lessons', function () {
        $user = User::factory()->create();
        $learningPath = LearningPath::factory()->create();
        $learningModule = LearningModule::factory()->for($learningPath)->create(['position' => 1]);
        $completedLesson = Lesson::factory()->for($learningModule)->create(['position' => 1]);
        $startedLesson = Lesson::factory()->for($learningModule)->create(['position' => 2]);
        LessonProgress::factory()->for($user)->for($completedLesson)->completed()->create();
        LessonProgress::factory()->for($user)->for($startedLesson)->started()->create();

        $nextLesson = (new LearningProgressService)->nextIncompleteLesson($user, $learningPath);

        expect($nextLesson?->is($startedLesson))->toBeTrue();
    });

    test('moves to the next module after completing the current module', function () {
        $user = User::factory()->create();
        $learningPath = LearningPath::factory()->create();
        $firstModule = LearningModule::factory()->for($learningPath)->create(['position' => 1]);
        $secondModule = LearningModule::factory()->for($learningPath)->create(['position' => 2]);
        $completedLesson = Lesson::factory()->for($firstModule)->create(['position' => 1]);
        $nextModuleLesson = Lesson::factory()->for($secondModule)->create(['position' => 1]);
        LessonProgress::factory()->for($user)->for($completedLesson)->completed()->create();

        $nextLesson = (new LearningProgressService)->nextIncompleteLesson($user, $learningPath);

        expect($nextLesson?->is($nextModuleLesson))->toBeTrue();
    });

    test('returns null after every lesson is completed', function () {
        $user = User::factory()->create();
        $learningPath = LearningPath::factory()->create();
        $learningModule = LearningModule::factory()->for($learningPath)->create(['position' => 1]);
        $lessons = Lesson::factory()
            ->count(2)
            ->for($learningModule)
            ->sequence(['position' => 1], ['position' => 2])
            ->create();
        $lessons->each(function (Lesson $lesson) use ($user): void {
            LessonProgress::factory()->for($user)->for($lesson)->completed()->create();
        });

        $nextLesson = (new LearningProgressService)->nextIncompleteLesson($user, $learningPath);

        expect($nextLesson)->toBeNull();
    });
});

describe('lab progress', function () {
    test('returns zero progress across current catalog labs', function () {
        $user = User::factory()->create();
        Lab::factory()->count(2)->create();

        $progress = (new LearningProgressService)->labProgress($user);

        expect($progress)->toBe([
            'total' => 2,
            'started' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'remaining' => 2,
        ]);
    });

    test('counts started lab progress', function () {
        $user = User::factory()->create();
        $labs = Lab::factory()->count(2)->create();
        LabProgress::factory()->for($user)->for($labs[0])->started()->create();

        $progress = (new LearningProgressService)->labProgress($user);

        expect($progress)->toBe([
            'total' => 2,
            'started' => 1,
            'in_progress' => 1,
            'completed' => 0,
            'remaining' => 2,
        ]);
    });

    test('counts completed labs as started and ignores another user progress', function () {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $labs = Lab::factory()->count(3)->create();
        LabProgress::factory()->for($user)->for($labs[0])->completed()->create();
        LabProgress::factory()->for($user)->for($labs[1])->started()->create();
        LabProgress::factory()->for($otherUser)->for($labs[2])->completed()->create();

        $progress = (new LearningProgressService)->labProgress($user);

        expect($progress)->toBe([
            'total' => 3,
            'started' => 2,
            'in_progress' => 1,
            'completed' => 1,
            'remaining' => 2,
        ]);
    });
});
