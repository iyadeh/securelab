<?php

namespace App\Services\Labs\Simulators;

use App\Services\Labs\Contracts\LabSimulator;
use App\Services\Labs\SimulationOutcome;
use App\Services\Labs\SimulationResult;

final class SqlInjectionAuthenticationBypassSimulator implements LabSimulator
{
    public function definition(): array
    {
        return [
            'instructions' => 'Compare ordinary synthetic credentials with input that would alter a boolean authentication condition in an unsafe query.',
            'fields' => [
                [
                    'key' => 'username',
                    'label' => 'Synthetic username',
                    'type' => 'text',
                    'placeholder' => 'analyst',
                    'max_length' => 128,
                ],
                [
                    'key' => 'password',
                    'label' => 'Synthetic password',
                    'type' => 'password',
                    'placeholder' => 'Enter a training value',
                    'max_length' => 128,
                ],
            ],
        ];
    }

    public function validationRules(): array
    {
        return [
            'input' => ['required', 'array'],
            'input.username' => ['required', 'string', 'max:128'],
            'input.password' => ['required', 'string', 'max:128'],
        ];
    }

    public function simulate(array $input): SimulationResult
    {
        $unsafeConditionChanged = str_contains(
            mb_strtolower($input['username']),
            "' or '1'='1",
        );
        $outcome = $unsafeConditionChanged
            ? SimulationOutcome::VulnerableBehavior
            : SimulationOutcome::NormalBehavior;
        $summary = $unsafeConditionChanged
            ? 'The fictional login would accept the request because unsafe query construction changes the credential check.'
            : 'The fictional login would reject these credentials because the training bypass condition was not reached.';

        return new SimulationResult(
            outcome: $outcome,
            summary: $summary,
            input: [
                'username' => $input['username'],
                'password' => '[synthetic value hidden]',
            ],
            steps: [
                [
                    'key' => 'input',
                    'type' => 'input',
                    'label' => 'Submitted credentials',
                    'description' => 'The simulator receives bounded synthetic credentials as inert text.',
                ],
                [
                    'key' => 'application',
                    'type' => 'application',
                    'label' => 'Unsafe query construction',
                    'description' => 'The fictional application would combine values with authentication query structure instead of binding them as data.',
                ],
                [
                    'key' => 'decision',
                    'type' => 'decision',
                    'label' => 'Authentication decision',
                    'description' => $unsafeConditionChanged
                        ? 'The modeled condition becomes true without valid credentials.'
                        : 'The modeled condition remains false for the submitted credentials.',
                ],
                [
                    'key' => 'output',
                    'type' => 'output',
                    'label' => 'Simulated response',
                    'description' => $unsafeConditionChanged
                        ? 'The fictional vulnerable application would create an authenticated session.'
                        : 'The fictional application would return a generic login failure.',
                ],
            ],
        );
    }
}
