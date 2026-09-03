<?php

namespace App\Http\Controllers;

use App\Models\Lab;
use App\Models\User;
use App\Services\Labs\Contracts\LabRuntime;
use App\Services\LearningProgressService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LabWorkspaceController extends Controller
{
    /**
     * Display a safe, deterministic simulation workspace.
     */
    public function show(
        Request $request,
        Lab $lab,
        LabRuntime $runtime,
        LearningProgressService $progressService,
    ): Response {
        /** @var User $user */
        $user = $request->user();
        $definition = $runtime->definition($lab);
        $flashedResult = $request->session()->get('simulation_result');
        $result = is_array($flashedResult)
            && ($flashedResult['lab_slug'] ?? null) === $lab->slug
            ? ($flashedResult['result'] ?? null)
            : null;

        return Inertia::render('lab-workspace', [
            'lab' => [
                'slug' => $lab->slug,
                'title' => $lab->title,
                'description' => $lab->description,
                'vulnerability_type' => $lab->vulnerability_type,
                'difficulty' => $lab->difficulty,
                'state' => $progressService->labState($user, $lab),
            ],
            'simulation' => [
                'supported' => $definition !== null,
                'instructions' => $definition['instructions'] ?? null,
                'fields' => $definition['fields'] ?? [],
                'result' => $result,
            ],
        ]);
    }
}
