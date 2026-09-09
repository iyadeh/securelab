<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\Lab;
use App\Models\LearningModule;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\User;
use App\Services\LearningProgressService;
use Illuminate\Database\Eloquent\Relations\Relation;
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
            ->select(['id', 'slug', 'title', 'difficulty'])
            ->with([
                'learningModules:id,learning_path_id,title,position',
                'learningModules.lessons:id,learning_module_id,slug,title,summary,position',
            ])
            ->orderBy('title')
            ->orderBy('slug')
            ->get();
        $labs = Lab::query()
            ->select(['id', 'slug', 'title', 'vulnerability_type'])
            ->orderBy('title')
            ->orderBy('slug')
            ->get();
        $achievements = Achievement::query()
            ->select(['id', 'slug', 'title', 'position'])
            ->with(
                'userAchievements',
                function (Relation $query) use ($user): void {
                    $query
                        ->select(['id', 'user_id', 'achievement_id', 'unlocked_at'])
                        ->whereBelongsTo($user, 'user');
                },
            )
            ->orderBy('position')
            ->orderBy('slug')
            ->get();
        $overview = $progressService->progressOverview($user, $learningPaths, $labs);
        $lessonProgress = $overview['lesson_progress'];
        $labProgress = $overview['lab_progress'];
        $unlockedAchievements = 0;
        $latestUnlockedAchievement = null;
        $latestUnlockedTimestamp = null;

        foreach ($achievements as $achievement) {
            $award = $achievement->userAchievements->first();

            if ($award === null) {
                continue;
            }

            $unlockedAchievements++;
            $unlockedTimestamp = $award->unlocked_at->getTimestamp();

            if (
                $latestUnlockedAchievement === null
                || $latestUnlockedTimestamp === null
                || $unlockedTimestamp > $latestUnlockedTimestamp
            ) {
                $latestUnlockedAchievement = $achievement;
                $latestUnlockedTimestamp = $unlockedTimestamp;

                continue;
            }

            if ($unlockedTimestamp !== $latestUnlockedTimestamp) {
                continue;
            }

            $positionComparison = $achievement->position <=> $latestUnlockedAchievement->position;

            if (
                $positionComparison < 0
                || ($positionComparison === 0 && strcmp($achievement->slug, $latestUnlockedAchievement->slug) < 0)
            ) {
                $latestUnlockedAchievement = $achievement;
            }
        }

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
            'achievementSummary' => [
                'total' => $achievements->count(),
                'unlocked' => $unlockedAchievements,
                'locked' => $achievements->count() - $unlockedAchievements,
                'latest_unlocked' => $latestUnlockedAchievement === null
                    ? null
                    : [
                        'slug' => $latestUnlockedAchievement->slug,
                        'title' => $latestUnlockedAchievement->title,
                    ],
            ],
            'learningPaths' => $learningPaths->map(
                function (LearningPath $learningPath) use ($overview): array {
                    $pathOverview = $overview['learning_path_overviews'][$learningPath->id];
                    $pathProgress = $pathOverview['progress'];
                    $nextLesson = $pathOverview['next_lesson'];
                    $nextLessonModule = $nextLesson === null
                        ? null
                        : $learningPath->learningModules->first(
                            fn (LearningModule $learningModule): bool => $learningModule->lessons->contains($nextLesson),
                        );

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
                        'next_lesson' => $nextLesson === null
                            ? null
                            : [
                                'slug' => $nextLesson->slug,
                                'title' => $nextLesson->title,
                                'summary' => $nextLesson->summary,
                                'module_title' => $nextLessonModule?->title,
                            ],
                        'modules' => $learningPath->learningModules->map(
                            function (LearningModule $learningModule) use ($overview): array {
                                $moduleOverview = $overview['learning_module_overviews'][$learningModule->id];
                                $moduleProgress = $moduleOverview['progress'];

                                return [
                                    'title' => $learningModule->title,
                                    'lesson_count' => $moduleProgress['total'],
                                    'completed_lessons' => $moduleProgress['completed'],
                                    'in_progress_lessons' => $moduleProgress['in_progress'],
                                    'completion_percentage' => $moduleProgress['completion_percentage'],
                                    'state' => $moduleOverview['state'],
                                ];
                            },
                        )->values(),
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
                                'summary' => $lesson->summary,
                                'module_title' => $learningModule->title,
                                'learning_path' => [
                                    'slug' => $learningPath->slug,
                                    'title' => $learningPath->title,
                                    'completed_lessons' => $overview['learning_path_overviews'][$learningPath->id]['progress']['completed'],
                                    'lesson_count' => $overview['learning_path_overviews'][$learningPath->id]['progress']['total'],
                                    'completion_percentage' => $overview['learning_path_overviews'][$learningPath->id]['progress']['completion_percentage'],
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
