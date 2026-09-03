<?php

namespace App\Services\Labs\Contracts;

use App\Models\Lab;
use App\Services\Labs\SimulationResult;

interface LabRuntime
{
    /**
     * @return array{
     *     instructions: string,
     *     fields: list<array{key: string, label: string, type: 'text'|'password', placeholder: string, max_length: int}>
     * }|null
     */
    public function definition(Lab $lab): ?array;

    /**
     * @return array<string, list<string>>
     */
    public function validationRules(Lab $lab): array;

    /**
     * @param  array<string, string>  $input
     */
    public function simulate(Lab $lab, array $input): ?SimulationResult;
}
