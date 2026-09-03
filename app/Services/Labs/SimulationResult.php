<?php

namespace App\Services\Labs;

final readonly class SimulationResult
{
    /**
     * @param  array<string, string>  $input
     * @param  list<array{key: string, type: 'input'|'application'|'decision'|'output', label: string, description: string}>  $steps
     */
    public function __construct(
        public SimulationOutcome $outcome,
        public string $summary,
        public array $input,
        public array $steps,
    ) {}

    /**
     * @return array{
     *     outcome: 'normal_behavior'|'vulnerable_behavior'|'rejected',
     *     summary: string,
     *     input: array<string, string>,
     *     steps: list<array{key: string, type: 'input'|'application'|'decision'|'output', label: string, description: string}>
     * }
     */
    public function toArray(): array
    {
        return [
            'outcome' => $this->outcome->value,
            'summary' => $this->summary,
            'input' => $this->input,
            'steps' => $this->steps,
        ];
    }
}
