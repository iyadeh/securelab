<?php

namespace App\Services\Labs\Simulators;

use App\Services\Labs\Contracts\LabSimulator;
use App\Services\Labs\SimulationOutcome;
use App\Services\Labs\SimulationResult;

final class WeakPasswordResetSimulator implements LabSimulator
{
    private const SYNTHETIC_ACCOUNT = 'victim@example.test';

    private const PREDICTABLE_TOKEN = 'RESET-2026';

    public function definition(): array
    {
        return [
            'instructions' => 'Use the fictional account victim@example.test with the fixed training token RESET-2026 to inspect a predictable-token workflow.',
            'fields' => [
                [
                    'key' => 'account_identifier',
                    'label' => 'Synthetic account identifier',
                    'type' => 'text',
                    'placeholder' => self::SYNTHETIC_ACCOUNT,
                    'max_length' => 128,
                ],
                [
                    'key' => 'reset_token',
                    'label' => 'Synthetic reset token',
                    'type' => 'text',
                    'placeholder' => self::PREDICTABLE_TOKEN,
                    'max_length' => 64,
                ],
            ],
        ];
    }

    public function validationRules(): array
    {
        return [
            'input' => ['required', 'array'],
            'input.account_identifier' => ['required', 'string', 'max:128'],
            'input.reset_token' => ['required', 'string', 'max:64'],
        ];
    }

    public function simulate(array $input): SimulationResult
    {
        $weakResetAccepted = mb_strtolower($input['account_identifier']) === self::SYNTHETIC_ACCOUNT
            && $input['reset_token'] === self::PREDICTABLE_TOKEN;
        $outcome = $weakResetAccepted
            ? SimulationOutcome::VulnerableBehavior
            : SimulationOutcome::NormalBehavior;
        $summary = $weakResetAccepted
            ? 'The fictional recovery workflow would accept a predictable token for the synthetic account.'
            : 'The fictional recovery workflow would reject this account and token combination.';

        return new SimulationResult(
            outcome: $outcome,
            summary: $summary,
            input: [
                'account_identifier' => $input['account_identifier'],
                'reset_token' => $input['reset_token'],
            ],
            steps: [
                [
                    'key' => 'input',
                    'type' => 'input',
                    'label' => 'Submitted recovery data',
                    'description' => 'The simulator receives a fictional account identifier and bounded training token.',
                ],
                [
                    'key' => 'application',
                    'type' => 'application',
                    'label' => 'Token validation',
                    'description' => 'The fictional workflow would compare the submitted value with a predictable synthetic token.',
                ],
                [
                    'key' => 'decision',
                    'type' => 'decision',
                    'label' => 'Recovery decision',
                    'description' => $weakResetAccepted
                        ? 'The modeled workflow accepts the predictable token for the selected account.'
                        : 'The modeled workflow rejects the submitted recovery values.',
                ],
                [
                    'key' => 'output',
                    'type' => 'output',
                    'label' => 'Simulated response',
                    'description' => $weakResetAccepted
                        ? 'The fictional application would expose a password-change step; no real account is changed.'
                        : 'The fictional application would return a generic invalid-token response.',
                ],
            ],
        );
    }
}
