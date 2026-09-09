<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\Lab;
use App\Models\LearningPath;
use App\Models\User;
use App\Services\LearningProgressService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the current learner's identity and concise learning overview.
     */
    public function index(Request $request, LearningProgressService $progressService): Response
    {
        /** @var User $user */
        $user = $request->user();

        $learningPaths = LearningPath::query()
            ->select(['id', 'slug', 'title'])
            ->with([
                'learningModules:id,learning_path_id,position',
                'learningModules.lessons:id,learning_module_id,position',
            ])
            ->orderBy('title')
            ->orderBy('slug')
            ->get();
        $labs = Lab::query()->select('id')->get();
        $overview = $progressService->progressOverview($user, $learningPaths, $labs);
        $lessonProgress = $overview['lesson_progress'];
        $labProgress = $overview['lab_progress'];
        $achievementTotal = Achievement::query()->count();
        $achievementUnlocked = $user->userAchievements()->count();

        return Inertia::render('profile', [
            'learner' => [
                'name' => $user->name,
                'email' => $user->email,
                'joined_at' => $user->created_at?->toISOString(),
            ],
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
                'total' => $achievementTotal,
                'unlocked' => $achievementUnlocked,
                'locked' => max(0, $achievementTotal - $achievementUnlocked),
            ],
            'learningPaths' => $learningPaths->map(
                function (LearningPath $learningPath) use ($overview): array {
                    $pathOverview = $overview['learning_path_overviews'][$learningPath->id];
                    $pathProgress = $pathOverview['progress'];

                    return [
                        'slug' => $learningPath->slug,
                        'title' => $learningPath->title,
                        'lesson_count' => $pathProgress['total'],
                        'completed_lessons' => $pathProgress['completed'],
                        'completion_percentage' => $pathProgress['completion_percentage'],
                        'state' => $pathOverview['state'],
                    ];
                },
            )->values(),
        ]);
    }
}
