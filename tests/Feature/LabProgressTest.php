<?php

use App\Models\Lab;
use App\Models\LabProgress;
use App\Models\User;
use Illuminate\Database\QueryException;

test('lab progress exposes its user and lab relationships', function () {
    $user = User::factory()->create();
    $lab = Lab::factory()->create();
    $progress = LabProgress::factory()->for($user)->for($lab)->started()->create();

    expect($progress->user->is($user))->toBeTrue()
        ->and($progress->lab->is($lab))->toBeTrue()
        ->and($user->labProgress->modelKeys())->toContain($progress->id)
        ->and($lab->progress->modelKeys())->toContain($progress->id);
});

test('started lab progress has no completion timestamp', function () {
    $progress = LabProgress::factory()->started()->create();

    expect($progress->started_at)->not->toBeNull()
        ->and($progress->completed_at)->toBeNull();
});

test('completed lab progress records its start and completion timestamps', function () {
    $progress = LabProgress::factory()->completed()->create();

    expect($progress->started_at)->not->toBeNull()
        ->and($progress->completed_at)->not->toBeNull()
        ->and($progress->completed_at?->greaterThanOrEqualTo($progress->started_at))->toBeTrue();
});

test('a user can have only one progress record per lab', function () {
    $progress = LabProgress::factory()->started()->create();

    expect(fn () => LabProgress::factory()
        ->for($progress->user)
        ->for($progress->lab)
        ->started()
        ->create())->toThrow(QueryException::class);
});

test('deleting a user removes their lab progress', function () {
    $progress = LabProgress::factory()->started()->create();

    $progress->user->delete();

    expect($progress->fresh())->toBeNull();
});

test('deleting a lab removes its progress', function () {
    $progress = LabProgress::factory()->started()->create();

    $progress->lab->delete();

    expect($progress->fresh())->toBeNull();
});
