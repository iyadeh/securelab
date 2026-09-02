<?php

namespace App\Http\Controllers;

use App\Models\LearningModule;
use App\Models\LearningPath;
use App\Models\User;
use App\Services\LearningProgressService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LearningPathController extends Controller
{
    /**
     * Display the available learning paths for the current user.
     */
    public function index(Request $request, LearningProgressService $progressService): Response
    {
        /** @var User $user */
        $user = $request->user();

        $learningPaths = LearningPath::query()
            ->select(['id', 'slug', 'title', 'description', 'difficulty'])
            ->with([
                'learningModules:id,learning_path_id,title,position',
                'learningModules.lessons:id,learning_module_id,title,estimated_minutes,position',
            ])
            ->orderBy('title')
            ->orderBy('slug')
            ->get();
        $overviews = $progressService->learningPathOverviews($user, $learningPaths);

        return Inertia::render('learning-paths', [
            'learningPaths' => $learningPaths->map(function (LearningPath $learningPath) use ($overviews): array {
                $overview = $overviews[$learningPath->id];
                $progress = $overview['progress'];

                return [
                    'slug' => $learningPath->slug,
                    'title' => $learningPath->title,
                    'description' => $learningPath->description,
                    'difficulty' => $learningPath->difficulty,
                    'module_count' => $learningPath->learningModules->count(),
                    'lesson_count' => $progress['total'],
                    'estimated_minutes' => $learningPath->learningModules->sum(
                        fn (LearningModule $learningModule): int => $learningModule->lessons->sum('estimated_minutes'),
                    ),
                    'completed_lessons' => $progress['completed'],
                    'completion_percentage' => $progress['completion_percentage'],
                    'is_complete' => $progress['total'] > 0 && $progress['remaining'] === 0,
                    'next_lesson' => $overview['next_lesson'] === null
                        ? null
                        : ['title' => $overview['next_lesson']->title],
                ];
            })->values(),
        ]);
    }
}
