<?php

namespace App\Services\Labs\Simulators;

use App\Services\Labs\Contracts\LabSimulator;
use App\Services\Labs\SimulationOutcome;
use App\Services\Labs\SimulationResult;

final class ReflectedXssSimulator implements LabSimulator
{
    public function definition(): array
    {
        return [
            'instructions' => 'Submit a search term. Script-like markup remains inert while the simulator models unsafe reflection into an HTML response.',
            'fields' => [
                [
                    'key' => 'query',
                    'label' => 'Synthetic search query',
                    'type' => 'text',
                    'placeholder' => 'security basics',
                    'max_length' => 256,
                ],
            ],
        ];
    }

    public function validationRules(): array
    {
        return [
            'input' => ['required', 'array'],
            'input.query' => ['required', 'string', 'max:256'],
        ];
    }

    public function simulate(array $input): SimulationResult
    {
        $wouldEnterScriptContext = str_contains(
            mb_strtolower($input['query']),
            '<script',
        );
        $outcome = $wouldEnterScriptContext
            ? SimulationOutcome::VulnerableBehavior
            : SimulationOutcome::NormalBehavior;
        $summary = $wouldEnterScriptContext
            ? 'The fictional search page would place script-capable markup into its response without encoding it.'
            : 'The fictional search page would reflect ordinary text without reaching the training vulnerability condition.';

        return new SimulationResult(
            outcome: $outcome,
            summary: $summary,
            input: ['query' => $input['query']],
            steps: [
                [
                    'key' => 'input',
                    'type' => 'input',
                    'label' => 'Submitted search value',
                    'description' => 'The search value is received as bounded inert text.',
                ],
                [
                    'key' => 'application',
                    'type' => 'application',
                    'label' => 'Response composition',
                    'description' => 'The fictional page would place the value into an HTML response without context-aware encoding.',
                ],
                [
                    'key' => 'decision',
                    'type' => 'decision',
                    'label' => 'Browser interpretation',
                    'description' => $wouldEnterScriptContext
                        ? 'The modeled browser would interpret the value as script-capable markup.'
                        : 'The modeled browser would display the value as ordinary page content.',
                ],
                [
                    'key' => 'output',
                    'type' => 'output',
                    'label' => 'Simulated response',
                    'description' => $wouldEnterScriptContext
                        ? 'Unsafe reflected behavior would occur in the fictional application; no JavaScript ran here.'
                        : 'The fictional search result would render normally.',
                ],
            ],
        );
    }
}
