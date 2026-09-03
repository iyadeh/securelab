<?php

namespace App\Services\Labs\Simulators;

use App\Services\Labs\Contracts\LabSimulator;
use App\Services\Labs\SimulationOutcome;
use App\Services\Labs\SimulationResult;

final class StoredXssSimulator implements LabSimulator
{
    public function definition(): array
    {
        return [
            'instructions' => 'Submit a synthetic comment. Markup remains inert while the simulator models fictional storage and later rendering.',
            'fields' => [
                [
                    'key' => 'comment',
                    'label' => 'Synthetic comment',
                    'type' => 'text',
                    'placeholder' => 'Thanks for the clear explanation.',
                    'max_length' => 512,
                ],
            ],
        ];
    }

    public function validationRules(): array
    {
        return [
            'input' => ['required', 'array'],
            'input.comment' => ['required', 'string', 'max:512'],
        ];
    }

    public function simulate(array $input): SimulationResult
    {
        $wouldPersistScriptMarkup = str_contains(
            mb_strtolower($input['comment']),
            '<script',
        );
        $outcome = $wouldPersistScriptMarkup
            ? SimulationOutcome::VulnerableBehavior
            : SimulationOutcome::NormalBehavior;
        $summary = $wouldPersistScriptMarkup
            ? 'The fictional comment flow would store script-capable markup and later render it without encoding.'
            : 'The fictional comment flow would store and display ordinary text without reaching the training vulnerability condition.';

        return new SimulationResult(
            outcome: $outcome,
            summary: $summary,
            input: ['comment' => $input['comment']],
            steps: [
                [
                    'key' => 'input',
                    'type' => 'input',
                    'label' => 'Submitted comment',
                    'description' => 'The simulator receives bounded comment content as inert text.',
                ],
                [
                    'key' => 'application',
                    'type' => 'application',
                    'label' => 'Fictional storage',
                    'description' => 'The modeled application would save the value in temporary fictional training state only.',
                ],
                [
                    'key' => 'decision',
                    'type' => 'decision',
                    'label' => 'Later rendering decision',
                    'description' => $wouldPersistScriptMarkup
                        ? 'The modeled view would render stored markup without context-aware encoding.'
                        : 'The modeled view would display the stored value as ordinary content.',
                ],
                [
                    'key' => 'output',
                    'type' => 'output',
                    'label' => 'Simulated response',
                    'description' => $wouldPersistScriptMarkup
                        ? 'Unsafe stored behavior would affect a fictional later viewer; no JavaScript ran here.'
                        : 'The fictional comment would display normally to later viewers.',
                ],
            ],
        );
    }
}
