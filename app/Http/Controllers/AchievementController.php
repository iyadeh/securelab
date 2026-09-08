<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AchievementController extends Controller
{
    /**
     * Display the current learner's persisted achievement state.
     */
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $achievements = Achievement::query()
            ->select(['id', 'slug', 'title', 'description', 'category', 'position'])
            ->orderBy('position')
            ->orderBy('slug')
            ->get();
        $awardsByAchievementId = $user->userAchievements()
            ->whereIn('achievement_id', $achievements->modelKeys())
            ->get(['achievement_id', 'unlocked_at'])
            ->keyBy('achievement_id');
        $unlockedCount = $awardsByAchievementId->count();

        return Inertia::render('achievements', [
            'summary' => [
                'total' => $achievements->count(),
                'unlocked' => $unlockedCount,
                'locked' => $achievements->count() - $unlockedCount,
            ],
            'achievements' => $achievements->map(function (Achievement $achievement) use ($awardsByAchievementId): array {
                $award = $awardsByAchievementId->get($achievement->id);

                return [
                    'slug' => $achievement->slug,
                    'title' => $achievement->title,
                    'description' => $achievement->description,
                    'category' => $achievement->category,
                    'position' => $achievement->position,
                    'state' => $award === null ? 'locked' : 'unlocked',
                    'unlocked_at' => $award?->unlocked_at?->toISOString(),
                ];
            })->values(),
        ]);
    }
}
