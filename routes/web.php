<?php

use App\Http\Controllers\AchievementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LabController;
use App\Http\Controllers\LabProgressController;
use App\Http\Controllers\LabSimulationController;
use App\Http\Controllers\LabWorkspaceController;
use App\Http\Controllers\LearningPathController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\LessonProgressController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgressController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
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
    Route::get('labs/{lab:slug}/workspace', [LabWorkspaceController::class, 'show'])
        ->name('labs.workspace');
    Route::post('labs/{lab:slug}/simulate', [LabSimulationController::class, 'store'])
        ->name('labs.simulate');
    Route::post('labs/{lab:slug}/start', [LabProgressController::class, 'start'])
        ->name('labs.start');
    Route::post('labs/{lab:slug}/complete', [LabProgressController::class, 'complete'])
        ->name('labs.complete');
    Route::get('labs/{lab:slug}', [LabController::class, 'show'])->name('labs.show');
    Route::get('progress', [ProgressController::class, 'index'])->name('progress');
    Route::get('achievements', [AchievementController::class, 'index'])->name('achievements');
    Route::get('profile', [ProfileController::class, 'index'])->name('profile.show');
});

require __DIR__.'/settings.php';
