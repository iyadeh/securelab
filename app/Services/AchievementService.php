<?php

namespace App\Services;

use App\Models\Achievement;
use App\Models\Lab;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\User;

class AchievementService
{
    public function __construct(private LearningProgressService $learningProgressService) {}

    /**
     * Award every achievement supported by the user's current authoritative progress.
     */
    public function syncFor(User $user): void
    {
        $learningPaths = LearningPath::query()
            ->select('id')
            ->with([
                'learningModules:id,learning_path_id,position',
                'learningModules.lessons:id,learning_module_id,position',
            ])
            ->get();
        $labs = Lab::query()->select('id')->get();
        $overview = $this->learningProgressService->progressOverview($user, $learningPaths, $labs);
        $eligibleSlugs = $this->eligibleAchievementSlugs($overview);

        if ($eligibleSlugs === []) {
            return;
        }

        $achievements = Achievement::query()
            ->whereIn('slug', $eligibleSlugs)
            ->get(['id']);
        $existingAchievementIds = $user->userAchievements()
            ->whereIn('achievement_id', $achievements->modelKeys())
            ->pluck('achievement_id');
        $unlockedAt = now();

        foreach ($achievements->whereNotIn('id', $existingAchievementIds->all()) as $achievement) {
            $user->userAchievements()->createOrFirst(
                ['achievement_id' => $achievement->id],
                ['unlocked_at' => $unlockedAt],
            );
        }
    }

    /**
     * @param  array{
     *     lesson_progress: array{total: int, started: int, in_progress: int, completed: int, remaining: int, completion_percentage: int},
     *     lab_progress: array{total: int, started: int, in_progress: int, completed: int, remaining: int, completion_percentage: int},
     *     learning_path_overviews: array<int, array{
     *         progress: array{total: int, started: int, in_progress: int, completed: int, remaining: int, completion_percentage: int},
     *         state: 'not_started'|'in_progress'|'completed',
     *         lesson_states: array<int, 'not_started'|'in_progress'|'completed'>,
     *         next_lesson: Lesson|null
     *     }>,
     *     lesson_states: array<int, 'not_started'|'in_progress'|'completed'>,
     *     lab_states: array<int, 'not_started'|'in_progress'|'completed'>
     * }  $overview
     * @return list<string>
     */
    private function eligibleAchievementSlugs(array $overview): array
    {
        $lessonProgress = $overview['lesson_progress'];
        $labProgress = $overview['lab_progress'];
        $hasCompletedPath = collect($overview['learning_path_overviews'])->contains(
            fn (array $pathOverview): bool => $pathOverview['progress']['total'] > 0
                && $pathOverview['progress']['completed'] === $pathOverview['progress']['total'],
        );
        $criteria = [
            'first-lesson' => $lessonProgress['completed'] >= 1,
            'first-lab' => $labProgress['completed'] >= 1,
            'path-complete' => $hasCompletedPath,
            'lab-series-complete' => $labProgress['total'] > 0
                && $labProgress['completed'] === $labProgress['total'],
            'security-foundations-complete' => $lessonProgress['total'] > 0
                && $labProgress['total'] > 0
                && $lessonProgress['completed'] === $lessonProgress['total']
                && $labProgress['completed'] === $labProgress['total'],
        ];

        return array_keys(array_filter($criteria));
    }
}
