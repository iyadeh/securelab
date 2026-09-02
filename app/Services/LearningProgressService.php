<?php

namespace App\Services;

use App\Models\Lab;
use App\Models\LabProgress;
use App\Models\LearningModule;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection as SupportCollection;

class LearningProgressService
{
    private const STATE_NOT_STARTED = 'not_started';

    private const STATE_IN_PROGRESS = 'in_progress';

    private const STATE_COMPLETED = 'completed';

    /**
     * Derive progress and the next lesson for several eagerly loaded paths with one progress query.
     *
     * @param  EloquentCollection<int, LearningPath>  $learningPaths
     * @return array<int, array{progress: array{total: int, started: int, in_progress: int, completed: int, remaining: int, completion_percentage: int}, next_lesson: Lesson|null}>
     */
    public function learningPathOverviews(User $user, EloquentCollection $learningPaths): array
    {
        if ($learningPaths->isEmpty()) {
            return [];
        }

        $learningPaths->loadMissing('learningModules.lessons');
        $lessons = $learningPaths
            ->flatMap(fn (LearningPath $learningPath): SupportCollection => $this->loadedLessonsForPath($learningPath))
            ->values();
        $progressByLessonId = $this->progressByLessonId($user, $lessons);
        $overviews = [];

        foreach ($learningPaths as $learningPath) {
            $overviews[$learningPath->id] = $this->summarizeLoadedLessons(
                $this->loadedLessonsForPath($learningPath),
                $progressByLessonId,
            );
        }

        return $overviews;
    }

    /**
     * Derive path, module, and lesson progress from one eagerly loaded curriculum graph.
     *
     * @return array{
     *     progress: array{total: int, started: int, in_progress: int, completed: int, remaining: int, completion_percentage: int},
     *     module_progress: array<int, array{total: int, started: int, in_progress: int, completed: int, remaining: int, completion_percentage: int}>,
     *     lesson_states: array<int, 'not_started'|'in_progress'|'completed'>,
     *     next_lesson: Lesson|null
     * }
     */
    public function learningPathDetailProgress(User $user, LearningPath $learningPath): array
    {
        $learningPath->loadMissing('learningModules.lessons');
        $lessons = $this->loadedLessonsForPath($learningPath);
        $progressByLessonId = $this->progressByLessonId($user, $lessons);
        $overview = $this->summarizeLoadedLessons($lessons, $progressByLessonId);
        $moduleProgress = [];

        foreach ($learningPath->learningModules as $learningModule) {
            $moduleProgress[$learningModule->id] = $this->summarizeLoadedLessons(
                $learningModule->lessons,
                $progressByLessonId,
            )['progress'];
        }

        return [
            'progress' => $overview['progress'],
            'module_progress' => $moduleProgress,
            'lesson_states' => $lessons->mapWithKeys(
                fn (Lesson $lesson): array => [
                    $lesson->id => $this->lessonStateFromProgress($progressByLessonId->get($lesson->id)),
                ],
            )->all(),
            'next_lesson' => $overview['next_lesson'],
        ];
    }

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

        return $this->labStateFromProgress($progress);
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
     * Derive aggregate progress and per-lab states for an already loaded catalog.
     *
     * @param  EloquentCollection<int, Lab>  $labs
     * @return array{
     *     progress: array{total: int, started: int, in_progress: int, completed: int, remaining: int},
     *     lab_states: array<int, 'not_started'|'in_progress'|'completed'>
     * }
     */
    public function labCatalogProgress(User $user, EloquentCollection $labs): array
    {
        if ($labs->isEmpty()) {
            return [
                'progress' => $this->progressCounts(0, 0, 0),
                'lab_states' => [],
            ];
        }

        $progressByLabId = $user->labProgress()
            ->whereIn('lab_id', $labs->modelKeys())
            ->get(['lab_id', 'completed_at'])
            ->keyBy('lab_id');
        $completed = $progressByLabId->whereNotNull('completed_at')->count();

        return [
            'progress' => $this->progressCounts($labs->count(), $progressByLabId->count(), $completed),
            'lab_states' => $labs->mapWithKeys(
                fn (Lab $lab): array => [
                    $lab->id => $this->labStateFromProgress($progressByLabId->get($lab->id)),
                ],
            )->all(),
        ];
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
     * @return SupportCollection<int, Lesson>
     */
    private function loadedLessonsForPath(LearningPath $learningPath): SupportCollection
    {
        return $learningPath->learningModules
            ->flatMap(fn (LearningModule $learningModule): EloquentCollection => $learningModule->lessons)
            ->values();
    }

    /**
     * @param  SupportCollection<int, Lesson>  $lessons
     * @return EloquentCollection<int, LessonProgress>
     */
    private function progressByLessonId(User $user, SupportCollection $lessons): EloquentCollection
    {
        if ($lessons->isEmpty()) {
            return new EloquentCollection;
        }

        return $user->lessonProgress()
            ->whereIn('lesson_id', $lessons->pluck('id'))
            ->get(['lesson_id', 'completed_at'])
            ->keyBy('lesson_id');
    }

    /**
     * @param  SupportCollection<int, Lesson>  $lessons
     * @param  EloquentCollection<int, LessonProgress>  $progressByLessonId
     * @return array{progress: array{total: int, started: int, in_progress: int, completed: int, remaining: int, completion_percentage: int}, next_lesson: Lesson|null}
     */
    private function summarizeLoadedLessons(
        SupportCollection $lessons,
        EloquentCollection $progressByLessonId,
    ): array {
        $started = $lessons->filter(
            fn (Lesson $lesson): bool => $progressByLessonId->has($lesson->id),
        )->count();
        $completed = $lessons->filter(
            fn (Lesson $lesson): bool => $progressByLessonId->get($lesson->id)?->completed_at !== null,
        )->count();
        $counts = $this->progressCounts($lessons->count(), $started, $completed);

        return [
            'progress' => [
                ...$counts,
                'completion_percentage' => $this->completionPercentage($completed, $lessons->count()),
            ],
            'next_lesson' => $lessons->first(
                fn (Lesson $lesson): bool => $progressByLessonId->get($lesson->id)?->completed_at === null,
            ),
        ];
    }

    /**
     * @return 'not_started'|'in_progress'|'completed'
     */
    private function lessonStateFromProgress(?LessonProgress $progress): string
    {
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
    private function labStateFromProgress(?LabProgress $progress): string
    {
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
