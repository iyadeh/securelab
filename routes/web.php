<?php

use App\Http\Controllers\LabController;
use App\Http\Controllers\LearningPathController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\LessonProgressController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
    Route::get('learn', [LearningPathController::class, 'index'])->name('learn');
    Route::get('learn/{learningPath:slug}', [LearningPathController::class, 'show'])
        ->name('learning-paths.show');
    Route::get('learn/{learningPath:slug}/{lesson:slug}', [LessonController::class, 'show'])
        ->scopeBindings()
        ->name('lessons.show');
    Route::post(
        'learn/{learningPath:slug}/{lesson:slug}/start',
        [LessonProgressController::class, 'start'],
    )
        ->scopeBindings()
        ->name('lessons.start');
    Route::post(
        'learn/{learningPath:slug}/{lesson:slug}/complete',
        [LessonProgressController::class, 'complete'],
    )
        ->scopeBindings()
        ->name('lessons.complete');
    Route::get('labs', [LabController::class, 'index'])->name('labs');
    Route::get('labs/{lab:slug}', [LabController::class, 'show'])->name('labs.show');
    Route::inertia('progress', 'progress')->name('progress');
    Route::inertia('achievements', 'achievements')->name('achievements');
    Route::inertia('profile', 'profile')->name('profile.show');
});

require __DIR__.'/settings.php';
