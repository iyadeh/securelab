<?php

namespace App\Http\Controllers;

use App\Http\Requests\SimulateLabRequest;
use App\Models\Lab;
use App\Services\Labs\Contracts\LabRuntime;
use Illuminate\Http\RedirectResponse;

class LabSimulationController extends Controller
{
    /**
     * Compute an ephemeral safe simulation result.
     */
    public function store(
        SimulateLabRequest $request,
        Lab $lab,
        LabRuntime $runtime,
    ): RedirectResponse {
        $validated = $request->validated();

        /** @var array<string, string> $input */
        $input = $validated['input'] ?? [];
        $result = $runtime->simulate($lab, $input);

        if ($result === null) {
            return to_route('labs.workspace', ['lab' => $lab->slug])
                ->withErrors([
                    'simulation' => 'This lab does not have a supported safe simulation.',
                ]);
        }

        return to_route('labs.workspace', ['lab' => $lab->slug])
            ->with('simulation_result', [
                'lab_slug' => $lab->slug,
                'result' => $result->toArray(),
            ]);
    }
}
