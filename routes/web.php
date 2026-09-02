<?php

use App\Http\Controllers\LearningPathController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
    Route::get('learn', [LearningPathController::class, 'index'])->name('learn');
    Route::get('learn/{learningPath:slug}', [LearningPathController::class, 'show'])
        ->name('learning-paths.show');
    Route::inertia('labs', 'labs')->name('labs');
    Route::inertia('progress', 'progress')->name('progress');
    Route::inertia('achievements', 'achievements')->name('achievements');
    Route::inertia('profile', 'profile')->name('profile.show');
});

require __DIR__.'/settings.php';
