<?php

namespace App\Http\Controllers;

use App\Models\LearningModule;
use App\Models\LearningPath;
use App\Models\Lesson;
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

    /**
     * Display one learning path and its curriculum for the current user.
     */
    public function show(
        Request $request,
        LearningPath $learningPath,
        LearningProgressService $progressService,
    ): Response {
        /** @var User $user */
        $user = $request->user();

        $learningPath->load([
            'learningModules:id,learning_path_id,title,description,position',
            'learningModules.lessons:id,learning_module_id,lab_id,slug,title,summary,estimated_minutes,position',
            'learningModules.lessons.lab:id,slug,title',
        ]);
        $detailProgress = $progressService->learningPathDetailProgress($user, $learningPath);
        $pathProgress = $detailProgress['progress'];

        return Inertia::render('learning-path-detail', [
            'learningPath' => [
                'slug' => $learningPath->slug,
                'title' => $learningPath->title,
                'description' => $learningPath->description,
                'difficulty' => $learningPath->difficulty,
                'module_count' => $learningPath->learningModules->count(),
                'lesson_count' => $pathProgress['total'],
                'estimated_minutes' => $learningPath->learningModules->sum(
                    fn (LearningModule $learningModule): int => $learningModule->lessons->sum('estimated_minutes'),
                ),
                'progress' => $pathProgress,
                'is_complete' => $pathProgress['total'] > 0 && $pathProgress['remaining'] === 0,
                'next_lesson' => $detailProgress['next_lesson'] === null
                    ? null
                    : [
                        'slug' => $detailProgress['next_lesson']->slug,
                        'title' => $detailProgress['next_lesson']->title,
                    ],
                'modules' => $learningPath->learningModules->map(
                    function (LearningModule $learningModule) use ($detailProgress): array {
                        $moduleProgress = $detailProgress['module_progress'][$learningModule->id];

                        return [
                            'title' => $learningModule->title,
                            'description' => $learningModule->description,
                            'position' => $learningModule->position,
                            'progress' => $moduleProgress,
                            'is_complete' => $moduleProgress['total'] > 0 && $moduleProgress['remaining'] === 0,
                            'lessons' => $learningModule->lessons->map(
                                fn (Lesson $lesson): array => [
                                    'slug' => $lesson->slug,
                                    'title' => $lesson->title,
                                    'summary' => $lesson->summary,
                                    'estimated_minutes' => $lesson->estimated_minutes,
                                    'position' => $lesson->position,
                                    'state' => $detailProgress['lesson_states'][$lesson->id],
                                    'is_next' => $detailProgress['next_lesson']?->is($lesson) ?? false,
                                    'has_lab' => $lesson->lab !== null,
                                    'lab' => $lesson->lab === null
                                        ? null
                                        : [
                                            'slug' => $lesson->lab->slug,
                                            'title' => $lesson->lab->title,
                                        ],
                                ],
                            )->values()->all(),
                        ];
                    },
                )->values()->all(),
            ],
        ]);
    }
}
