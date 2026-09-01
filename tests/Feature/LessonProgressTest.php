<?php

use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Database\QueryException;

test('new users start without learning progress', function () {
    $user = User::factory()->create();

    expect($user->lessonProgress)->toBeEmpty()
        ->and($user->labProgress)->toBeEmpty();
});

test('lesson progress exposes its user and lesson relationships', function () {
    $user = User::factory()->create();
    $lesson = Lesson::factory()->create();
    $progress = LessonProgress::factory()->for($user)->for($lesson)->started()->create();

    expect($progress->user->is($user))->toBeTrue()
        ->and($progress->lesson->is($lesson))->toBeTrue()
        ->and($user->lessonProgress->modelKeys())->toContain($progress->id)
        ->and($lesson->progress->modelKeys())->toContain($progress->id);
});

test('started lesson progress has no completion timestamp', function () {
    $progress = LessonProgress::factory()->started()->create();

    expect($progress->started_at)->not->toBeNull()
        ->and($progress->completed_at)->toBeNull();
});

test('completed lesson progress records its start and completion timestamps', function () {
    $progress = LessonProgress::factory()->completed()->create();

    expect($progress->started_at)->not->toBeNull()
        ->and($progress->completed_at)->not->toBeNull()
        ->and($progress->completed_at?->greaterThanOrEqualTo($progress->started_at))->toBeTrue();
});

test('a user can have only one progress record per lesson', function () {
    $progress = LessonProgress::factory()->started()->create();

    expect(fn () => LessonProgress::factory()
        ->for($progress->user)
        ->for($progress->lesson)
        ->started()
        ->create())->toThrow(QueryException::class);
});

test('deleting a user removes their lesson progress', function () {
    $progress = LessonProgress::factory()->started()->create();

    $progress->user->delete();

    expect($progress->fresh())->toBeNull();
});

test('deleting a lesson removes its progress', function () {
    $progress = LessonProgress::factory()->started()->create();

    $progress->lesson->delete();

    expect($progress->fresh())->toBeNull();
});
