<?php

namespace App\Services\Labs;

use App\Models\Lab;
use App\Services\Labs\Contracts\LabRuntime;
use App\Services\Labs\Contracts\LabSimulator;
use App\Services\Labs\Simulators\IdorSimulator;
use App\Services\Labs\Simulators\ReflectedXssSimulator;
use App\Services\Labs\Simulators\SqlInjectionAuthenticationBypassSimulator;
use App\Services\Labs\Simulators\StoredXssSimulator;
use App\Services\Labs\Simulators\WeakPasswordResetSimulator;

final class SimulationLabRuntime implements LabRuntime
{
    /** @var array<string, LabSimulator> */
    private array $simulators;

    public function __construct(
        SqlInjectionAuthenticationBypassSimulator $sqlInjectionAuthenticationBypass,
        ReflectedXssSimulator $reflectedXss,
        IdorSimulator $idor,
        WeakPasswordResetSimulator $weakPasswordReset,
        StoredXssSimulator $storedXss,
    ) {
        $this->simulators = [
            'sql-injection-authentication-bypass' => $sqlInjectionAuthenticationBypass,
            'reflected-xss' => $reflectedXss,
            'idor' => $idor,
            'weak-password-reset' => $weakPasswordReset,
            'stored-xss' => $storedXss,
        ];
    }

    public function definition(Lab $lab): ?array
    {
        return $this->simulatorFor($lab)?->definition();
    }

    public function validationRules(Lab $lab): array
    {
        return $this->simulatorFor($lab)?->validationRules() ?? [];
    }

    public function simulate(Lab $lab, array $input): ?SimulationResult
    {
        return $this->simulatorFor($lab)?->simulate($input);
    }

    private function simulatorFor(Lab $lab): ?LabSimulator
    {
        return $this->simulators[$lab->slug] ?? null;
    }
}
