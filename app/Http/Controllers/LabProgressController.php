<?php

namespace App\Http\Controllers;

use App\Models\Lab;
use App\Models\User;
use App\Services\AchievementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LabProgressController extends Controller
{
    /**
     * Mark a lab as started for the current user.
     */
    public function start(Request $request, Lab $lab): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->labProgress()->firstOrCreate(
            ['lab_id' => $lab->id],
            ['started_at' => now()],
        );

        return $this->redirectToWorkspace($lab);
    }

    /**
     * Mark a lab as completed for the current user.
     */
    public function complete(
        Request $request,
        Lab $lab,
        AchievementService $achievementService,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();
        $timestamp = now();
        $progress = $user->labProgress()->firstOrCreate(
            ['lab_id' => $lab->id],
            [
                'started_at' => $timestamp,
                'completed_at' => $timestamp,
            ],
        );

        if (! $progress->wasRecentlyCreated) {
            $user->labProgress()
                ->whereKey($progress->id)
                ->whereNull('completed_at')
                ->update(['completed_at' => $timestamp]);
        }

        $achievementService->syncFor($user);

        return $this->redirectToWorkspace($lab);
    }

    private function redirectToWorkspace(Lab $lab): RedirectResponse
    {
        return to_route('labs.workspace', ['lab' => $lab->slug]);
    }
}
