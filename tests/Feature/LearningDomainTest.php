<?php

use App\Models\Lab;
use App\Models\LearningModule;
use App\Models\LearningPath;
use App\Models\Lesson;
use Illuminate\Database\QueryException;

test('learning paths expose learning modules in explicit position order', function () {
    $learningPath = LearningPath::factory()->create();
    $lastModule = LearningModule::factory()->for($learningPath)->create(['position' => 20]);
    $firstModule = LearningModule::factory()->for($learningPath)->create(['position' => 10]);

    $moduleIds = $learningPath->learningModules()->get()->modelKeys();

    expect($moduleIds)->toBe([$firstModule->id, $lastModule->id]);
});

test('learning modules belong to a learning path', function () {
    $learningPath = LearningPath::factory()->create();
    $learningModule = LearningModule::factory()->for($learningPath)->create();

    expect($learningModule->learningPath->is($learningPath))->toBeTrue();
});

test('learning modules expose lessons in explicit position order', function () {
    $learningModule = LearningModule::factory()->create();
    $lastLesson = Lesson::factory()->for($learningModule)->create(['position' => 20]);
    $firstLesson = Lesson::factory()->for($learningModule)->create(['position' => 10]);

    $lessonIds = $learningModule->lessons()->get()->modelKeys();

    expect($lessonIds)->toBe([$firstLesson->id, $lastLesson->id]);
});

test('lessons belong to a learning module', function () {
    $learningModule = LearningModule::factory()->create();
    $lesson = Lesson::factory()->for($learningModule)->create();

    expect($lesson->learningModule->is($learningModule))->toBeTrue();
});

test('lessons may reference a lab', function () {
    $lab = Lab::factory()->create();
    $lessonWithoutLab = Lesson::factory()->create();
    $lessonWithLab = Lesson::factory()->for($lab)->create();

    expect($lessonWithoutLab->lab)->toBeNull()
        ->and($lessonWithLab->lab->is($lab))->toBeTrue()
        ->and($lab->lessons->modelKeys())->toContain($lessonWithLab->id);
});

test('labs can be created independently', function () {
    $lab = Lab::factory()->create();

    $this->assertModelExists($lab);
    expect($lab->lessons)->toBeEmpty();
});

test('module positions are unique within each learning path', function () {
    $learningPath = LearningPath::factory()->create();
    $otherLearningPath = LearningPath::factory()->create();
    LearningModule::factory()->for($learningPath)->create(['position' => 1]);
    LearningModule::factory()->for($otherLearningPath)->create(['position' => 1]);

    expect(fn () => LearningModule::factory()->for($learningPath)->create(['position' => 1]))
        ->toThrow(QueryException::class);
});

test('lesson positions are unique within each learning module', function () {
    $learningModule = LearningModule::factory()->create();
    $otherLearningModule = LearningModule::factory()->create();
    Lesson::factory()->for($learningModule)->create(['position' => 1]);
    Lesson::factory()->for($otherLearningModule)->create(['position' => 1]);

    expect(fn () => Lesson::factory()->for($learningModule)->create(['position' => 1]))
        ->toThrow(QueryException::class);
});

test('public slugs are unique', function (string $modelClass) {
    $modelClass::factory()->create(['slug' => 'duplicate-slug']);

    expect(fn () => $modelClass::factory()->create(['slug' => 'duplicate-slug']))
        ->toThrow(QueryException::class);
})->with([
    'learning path' => LearningPath::class,
    'lesson' => Lesson::class,
    'lab' => Lab::class,
]);

test('difficulty values use the supported product levels', function (string $modelClass) {
    expect(fn () => $modelClass::factory()->create(['difficulty' => 'impossible']))
        ->toThrow(QueryException::class);
})->with([
    'learning path' => LearningPath::class,
    'lab' => Lab::class,
]);

test('factories create valid learning graphs', function () {
    $learningPath = LearningPath::factory()
        ->has(
            LearningModule::factory()
                ->count(2)
                ->has(Lesson::factory()->count(2), 'lessons'),
            'learningModules',
        )
        ->create();

    $learningPath->load('learningModules.lessons');

    expect($learningPath->learningModules)->toHaveCount(2)
        ->and($learningPath->learningModules->first()->lessons)->toHaveCount(2)
        ->and($learningPath->learningModules->last()->lessons)->toHaveCount(2);
});

test('deleting a learning path removes its learning structure but preserves linked labs', function () {
    $learningPath = LearningPath::factory()->create();
    $learningModule = LearningModule::factory()->for($learningPath)->create();
    $lab = Lab::factory()->create();
    $lesson = Lesson::factory()->for($learningModule)->for($lab)->create();

    $learningPath->delete();

    expect($learningModule->fresh())->toBeNull()
        ->and($lesson->fresh())->toBeNull()
        ->and($lab->fresh())->not->toBeNull();
});

test('deleting a lab clears its lesson reference', function () {
    $lab = Lab::factory()->create();
    $lesson = Lesson::factory()->for($lab)->create();

    $lab->delete();

    expect($lesson->fresh()->lab_id)->toBeNull();
});
