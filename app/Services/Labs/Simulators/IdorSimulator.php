<?php

namespace App\Services\Labs\Simulators;

use App\Services\Labs\Contracts\LabSimulator;
use App\Services\Labs\SimulationOutcome;
use App\Services\Labs\SimulationResult;

final class IdorSimulator implements LabSimulator
{
    private const OWNED_RESOURCE = '1001';

    private const OTHER_ACCOUNT_RESOURCE = '2002';

    public function definition(): array
    {
        return [
            'instructions' => 'The synthetic learner owns resource 1001. Request resource 2002 to inspect a fictional endpoint with no ownership check.',
            'fields' => [
                [
                    'key' => 'resource_id',
                    'label' => 'Synthetic resource identifier',
                    'type' => 'text',
                    'placeholder' => self::OWNED_RESOURCE,
                    'max_length' => 16,
                ],
            ],
        ];
    }

    public function validationRules(): array
    {
        return [
            'input' => ['required', 'array'],
            'input.resource_id' => ['required', 'string', 'max:16', 'regex:/\A[0-9]+\z/'],
        ];
    }

    public function simulate(array $input): SimulationResult
    {
        $resourceId = $input['resource_id'];
        $outcome = match ($resourceId) {
            self::OTHER_ACCOUNT_RESOURCE => SimulationOutcome::VulnerableBehavior,
            self::OWNED_RESOURCE => SimulationOutcome::NormalBehavior,
            default => SimulationOutcome::Rejected,
        };
        $summary = match ($outcome) {
            SimulationOutcome::VulnerableBehavior => 'The fictional endpoint would disclose another account\'s resource because it checks existence but not ownership.',
            SimulationOutcome::NormalBehavior => 'The fictional endpoint would return the resource owned by the synthetic learner.',
            SimulationOutcome::Rejected => 'The fictional endpoint would return a not-found response for this synthetic identifier.',
        };

        return new SimulationResult(
            outcome: $outcome,
            summary: $summary,
            input: ['resource_id' => $resourceId],
            steps: [
                [
                    'key' => 'input',
                    'type' => 'input',
                    'label' => 'Requested identifier',
                    'description' => 'The simulator receives a bounded numeric identifier for a fictional resource.',
                ],
                [
                    'key' => 'application',
                    'type' => 'application',
                    'label' => 'Synthetic resource lookup',
                    'description' => 'The fictional endpoint would look up a training record by identifier.',
                ],
                [
                    'key' => 'decision',
                    'type' => 'decision',
                    'label' => 'Ownership decision',
                    'description' => $resourceId === self::OTHER_ACCOUNT_RESOURCE
                        ? 'The modeled endpoint omits the ownership check for an existing resource.'
                        : 'No cross-account disclosure is modeled for this identifier.',
                ],
                [
                    'key' => 'output',
                    'type' => 'output',
                    'label' => 'Simulated response',
                    'description' => match ($outcome) {
                        SimulationOutcome::VulnerableBehavior => 'The fictional response would expose another account\'s synthetic profile.',
                        SimulationOutcome::NormalBehavior => 'The fictional response would show the learner\'s synthetic profile.',
                        SimulationOutcome::Rejected => 'The fictional response would contain no profile data.',
                    },
                ],
            ],
        );
    }
}
