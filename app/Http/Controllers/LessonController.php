<?php

namespace App\Http\Controllers;

use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\User;
use App\Services\LearningProgressService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LessonController extends Controller
{
    /**
     * Display one lesson in its curriculum context.
     */
    public function show(
        Request $request,
        LearningPath $learningPath,
        Lesson $lesson,
        LearningProgressService $progressService,
    ): Response {
        /** @var User $user */
        $user = $request->user();

        $lesson->load([
            'learningModule:id,learning_path_id,title,position',
            'lab:id,slug,title,difficulty,vulnerability_type',
        ]);
        $orderedLessons = $learningPath->lessons()
            ->get(['lessons.id', 'lessons.slug', 'lessons.title']);
        $currentLessonIndex = $orderedLessons->search(
            fn (Lesson $orderedLesson): bool => $orderedLesson->is($lesson),
        );

        abort_if($currentLessonIndex === false, 404);

        $previousLesson = $currentLessonIndex > 0
            ? $orderedLessons->get($currentLessonIndex - 1)
            : null;
        $nextLesson = $orderedLessons->get($currentLessonIndex + 1);

        return Inertia::render('lesson', [
            'learningPath' => [
                'slug' => $learningPath->slug,
                'title' => $learningPath->title,
            ],
            'module' => [
                'title' => $lesson->learningModule->title,
                'position' => $lesson->learningModule->position,
            ],
            'lesson' => [
                'slug' => $lesson->slug,
                'title' => $lesson->title,
                'summary' => $lesson->summary,
                'content' => $lesson->content,
                'estimated_minutes' => $lesson->estimated_minutes,
                'position' => $lesson->position,
                'state' => $progressService->lessonState($user, $lesson),
            ],
            'lab' => $lesson->lab === null
                ? null
                : [
                    'slug' => $lesson->lab->slug,
                    'title' => $lesson->lab->title,
                    'difficulty' => $lesson->lab->difficulty,
                    'vulnerability_type' => $lesson->lab->vulnerability_type,
                ],
            'previousLesson' => $this->lessonSummary($previousLesson),
            'nextLesson' => $this->lessonSummary($nextLesson),
        ]);
    }

    /**
     * @return array{slug: string, title: string}|null
     */
    private function lessonSummary(?Lesson $lesson): ?array
    {
        if ($lesson === null) {
            return null;
        }

        return [
            'slug' => $lesson->slug,
            'title' => $lesson->title,
        ];
    }
}
