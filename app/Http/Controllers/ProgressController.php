<?php

namespace App\Http\Controllers;

use App\Models\Lab;
use App\Models\LearningModule;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\User;
use App\Services\LearningProgressService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection as SupportCollection;
use Inertia\Inertia;
use Inertia\Response;

class ProgressController extends Controller
{
    /**
     * Display the current user's derived learning and lab progress.
     */
    public function index(Request $request, LearningProgressService $progressService): Response
    {
        /** @var User $user */
        $user = $request->user();

        $learningPaths = LearningPath::query()
            ->select(['id', 'slug', 'title', 'difficulty'])
            ->with([
                'learningModules:id,learning_path_id,title,position',
                'learningModules.lessons:id,learning_module_id,slug,title,position',
            ])
            ->orderBy('title')
            ->orderBy('slug')
            ->get();
        $labs = Lab::query()
            ->select(['id', 'slug', 'title', 'vulnerability_type'])
            ->orderBy('title')
            ->orderBy('slug')
            ->get();
        $overview = $progressService->progressOverview($user, $learningPaths, $labs);

        return Inertia::render('progress', [
            'lessonProgress' => $overview['lesson_progress'],
            'labProgress' => $overview['lab_progress'],
            'learningPaths' => $learningPaths->map(
                function (LearningPath $learningPath) use ($overview): array {
                    $pathOverview = $overview['learning_path_overviews'][$learningPath->id];
                    $pathProgress = $pathOverview['progress'];

                    return [
                        'slug' => $learningPath->slug,
                        'title' => $learningPath->title,
                        'difficulty' => $learningPath->difficulty,
                        'module_count' => $learningPath->learningModules->count(),
                        'lesson_count' => $pathProgress['total'],
                        'completed_lessons' => $pathProgress['completed'],
                        'in_progress_lessons' => $pathProgress['in_progress'],
                        'completion_percentage' => $pathProgress['completion_percentage'],
                        'state' => $pathOverview['state'],
                        'next_lesson' => $pathOverview['next_lesson'] === null
                            ? null
                            : [
                                'slug' => $pathOverview['next_lesson']->slug,
                                'title' => $pathOverview['next_lesson']->title,
                            ],
                    ];
                },
            )->values(),
            'inProgressLessons' => $learningPaths
                ->flatMap(
                    fn (LearningPath $learningPath): SupportCollection => $learningPath->learningModules->flatMap(
                        fn (LearningModule $learningModule): SupportCollection => $learningModule->lessons
                            ->filter(
                                fn (Lesson $lesson): bool => $overview['lesson_states'][$lesson->id] === 'in_progress',
                            )
                            ->map(fn (Lesson $lesson): array => [
                                'slug' => $lesson->slug,
                                'title' => $lesson->title,
                                'module_title' => $learningModule->title,
                                'learning_path' => [
                                    'slug' => $learningPath->slug,
                                    'title' => $learningPath->title,
                                ],
                            ]),
                    ),
                )
                ->values(),
            'inProgressLabs' => $labs
                ->filter(
                    fn (Lab $lab): bool => $overview['lab_states'][$lab->id] === 'in_progress',
                )
                ->map(fn (Lab $lab): array => [
                    'slug' => $lab->slug,
                    'title' => $lab->title,
                    'vulnerability_type' => $lab->vulnerability_type,
                ])
                ->values(),
        ]);
    }
}
