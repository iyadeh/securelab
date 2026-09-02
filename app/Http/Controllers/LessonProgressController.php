<?php

namespace App\Http\Controllers;

use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LessonProgressController extends Controller
{
    /**
     * Mark a lesson as started for the current user.
     */
    public function start(
        Request $request,
        LearningPath $learningPath,
        Lesson $lesson,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();

        $user->lessonProgress()->firstOrCreate(
            ['lesson_id' => $lesson->id],
            ['started_at' => now()],
        );

        return $this->redirectToLesson($learningPath, $lesson);
    }

    /**
     * Mark a lesson as completed for the current user.
     */
    public function complete(
        Request $request,
        LearningPath $learningPath,
        Lesson $lesson,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();
        $timestamp = now();
        $progress = $user->lessonProgress()->firstOrCreate(
            ['lesson_id' => $lesson->id],
            [
                'started_at' => $timestamp,
                'completed_at' => $timestamp,
            ],
        );

        if (! $progress->wasRecentlyCreated) {
            $user->lessonProgress()
                ->whereKey($progress->id)
                ->whereNull('completed_at')
                ->update(['completed_at' => $timestamp]);
        }

        return $this->redirectToLesson($learningPath, $lesson);
    }

    private function redirectToLesson(
        LearningPath $learningPath,
        Lesson $lesson,
    ): RedirectResponse {
        return to_route('lessons.show', [
            'learningPath' => $learningPath->slug,
            'lesson' => $lesson->slug,
        ]);
    }
}
