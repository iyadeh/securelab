<?php

namespace App\Services\Labs\Contracts;

use App\Services\Labs\SimulationResult;

interface LabSimulator
{
    /**
     * @return array{
     *     instructions: string,
     *     fields: list<array{key: string, label: string, type: 'text'|'password', placeholder: string, max_length: int}>
     * }
     */
    public function definition(): array;

    /**
     * @return array<string, list<string>>
     */
    public function validationRules(): array;

    /**
     * @param  array<string, string>  $input
     */
    public function simulate(array $input): SimulationResult;
}
