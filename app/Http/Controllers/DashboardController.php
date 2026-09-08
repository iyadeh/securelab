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

class DashboardController extends Controller
{
    /**
     * Display the current learner's compact progress overview.
     */
    public function index(Request $request, LearningProgressService $progressService): Response
    {
        /** @var User $user */
        $user = $request->user();

        $learningPaths = LearningPath::query()
            ->select(['id', 'slug', 'title'])
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
        $lessonProgress = $overview['lesson_progress'];
        $labProgress = $overview['lab_progress'];

        return Inertia::render('dashboard', [
            'learningSummary' => [
                'total' => $lessonProgress['total'],
                'in_progress' => $lessonProgress['in_progress'],
                'completed' => $lessonProgress['completed'],
                'remaining' => $lessonProgress['remaining'],
                'completion_percentage' => $lessonProgress['completion_percentage'],
            ],
            'labSummary' => [
                'total' => $labProgress['total'],
                'in_progress' => $labProgress['in_progress'],
                'completed' => $labProgress['completed'],
                'remaining' => $labProgress['remaining'],
                'completion_percentage' => $labProgress['completion_percentage'],
            ],
            'learningPaths' => $learningPaths->map(
                function (LearningPath $learningPath) use ($overview): array {
                    $pathOverview = $overview['learning_path_overviews'][$learningPath->id];
                    $pathProgress = $pathOverview['progress'];

                    return [
                        'slug' => $learningPath->slug,
                        'title' => $learningPath->title,
                        'module_count' => $learningPath->learningModules->count(),
                        'lesson_count' => $pathProgress['total'],
                        'completed_lessons' => $pathProgress['completed'],
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
                ->take(2)
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
                ->take(2)
                ->values(),
        ]);
    }
}
