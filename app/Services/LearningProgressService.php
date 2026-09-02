<?php

namespace App\Services;

use App\Models\Lab;
use App\Models\LearningModule;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class LearningProgressService
{
    private const STATE_NOT_STARTED = 'not_started';

    private const STATE_IN_PROGRESS = 'in_progress';

    private const STATE_COMPLETED = 'completed';

    /**
     * Completion percentage is rounded to the nearest whole number using half-up rounding.
     *
     * @return array{total: int, started: int, in_progress: int, completed: int, remaining: int, completion_percentage: int}
     */
    public function learningPathProgress(User $user, LearningPath $learningPath): array
    {
        $counts = $this->lessonCounts($user, $this->lessonsForPath($learningPath));

        return [
            ...$counts,
            'completion_percentage' => $this->completionPercentage($counts['completed'], $counts['total']),
        ];
    }

    /**
     * @return array{total: int, started: int, in_progress: int, completed: int, remaining: int}
     */
    public function learningModuleProgress(User $user, LearningModule $learningModule): array
    {
        return $this->lessonCounts($user, $this->lessonsForModule($learningModule));
    }

    public function nextIncompleteLesson(User $user, LearningPath $learningPath): ?Lesson
    {
        return Lesson::query()
            ->select('lessons.*')
            ->join('learning_modules', 'learning_modules.id', '=', 'lessons.learning_module_id')
            ->where('learning_modules.learning_path_id', $learningPath->id)
            ->whereDoesntHave(
                'progress',
                fn (Builder $query): Builder => $query
                    ->whereBelongsTo($user, 'user')
                    ->whereNotNull('completed_at'),
            )
            ->orderBy('learning_modules.position')
            ->orderBy('lessons.position')
            ->first();
    }

    /**
     * @return 'not_started'|'in_progress'|'completed'
     */
    public function lessonState(User $user, Lesson $lesson): string
    {
        $progress = $user->lessonProgress()
            ->whereBelongsTo($lesson, 'lesson')
            ->first(['id', 'completed_at']);

        if ($progress === null) {
            return self::STATE_NOT_STARTED;
        }

        if ($progress->completed_at !== null) {
            return self::STATE_COMPLETED;
        }

        return self::STATE_IN_PROGRESS;
    }

    /**
     * @return 'not_started'|'in_progress'|'completed'
     */
    public function labState(User $user, Lab $lab): string
    {
        $progress = $user->labProgress()
            ->whereBelongsTo($lab, 'lab')
            ->first(['id', 'completed_at']);

        if ($progress === null) {
            return self::STATE_NOT_STARTED;
        }

        if ($progress->completed_at !== null) {
            return self::STATE_COMPLETED;
        }

        return self::STATE_IN_PROGRESS;
    }

    /**
     * @return array{total: int, started: int, in_progress: int, completed: int, remaining: int}
     */
    public function labProgress(User $user): array
    {
        $progress = $user->labProgress()->get(['started_at', 'completed_at']);

        return $this->progressCounts(
            Lab::query()->count(),
            $progress->count(),
            $progress->whereNotNull('completed_at')->count(),
        );
    }

    /**
     * @param  Builder<Lesson>  $lessons
     * @return array{total: int, started: int, in_progress: int, completed: int, remaining: int}
     */
    private function lessonCounts(User $user, Builder $lessons): array
    {
        $total = (clone $lessons)->count();
        $lessonIds = (clone $lessons)->select('lessons.id');
        $progress = $user->lessonProgress()
            ->whereIn('lesson_id', $lessonIds)
            ->get(['started_at', 'completed_at']);

        return $this->progressCounts(
            $total,
            $progress->count(),
            $progress->whereNotNull('completed_at')->count(),
        );
    }

    /**
     * @return Builder<Lesson>
     */
    private function lessonsForPath(LearningPath $learningPath): Builder
    {
        return Lesson::query()->whereHas(
            'learningModule',
            fn (Builder $query): Builder => $query->whereBelongsTo($learningPath, 'learningPath'),
        );
    }

    /**
     * @return Builder<Lesson>
     */
    private function lessonsForModule(LearningModule $learningModule): Builder
    {
        return Lesson::query()->whereBelongsTo($learningModule, 'learningModule');
    }

    /**
     * @return array{total: int, started: int, in_progress: int, completed: int, remaining: int}
     */
    private function progressCounts(int $total, int $started, int $completed): array
    {
        return [
            'total' => $total,
            'started' => $started,
            'in_progress' => $started - $completed,
            'completed' => $completed,
            'remaining' => max(0, $total - $completed),
        ];
    }

    private function completionPercentage(int $completed, int $total): int
    {
        if ($total === 0) {
            return 0;
        }

        $percentage = (int) round(($completed / $total) * 100, 0, PHP_ROUND_HALF_UP);

        return min(100, max(0, $percentage));
    }
}
