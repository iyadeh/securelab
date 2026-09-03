<?php

use App\Models\Lab;
use App\Services\Labs\SimulationLabRuntime;
use App\Services\Labs\Simulators\IdorSimulator;
use App\Services\Labs\Simulators\ReflectedXssSimulator;
use App\Services\Labs\Simulators\SqlInjectionAuthenticationBypassSimulator;
use App\Services\Labs\Simulators\StoredXssSimulator;
use App\Services\Labs\Simulators\WeakPasswordResetSimulator;

function makeSimulationLabRuntime(): SimulationLabRuntime
{
    return new SimulationLabRuntime(
        new SqlInjectionAuthenticationBypassSimulator,
        new ReflectedXssSimulator,
        new IdorSimulator,
        new WeakPasswordResetSimulator,
        new StoredXssSimulator,
    );
}

function makeSimulationLab(string $slug): Lab
{
    $lab = new Lab;
    $lab->slug = $slug;

    return $lab;
}

dataset('canonical vulnerable simulation input', [
    'SQL injection authentication bypass' => [
        'sql-injection-authentication-bypass',
        ['username' => "admin' OR '1'='1", 'password' => 'synthetic'],
    ],
    'reflected XSS' => [
        'reflected-xss',
        ['query' => '<script>alert("training")</script>'],
    ],
    'IDOR' => [
        'idor',
        ['resource_id' => '2002'],
    ],
    'weak password reset' => [
        'weak-password-reset',
        ['account_identifier' => 'victim@example.test', 'reset_token' => 'RESET-2026'],
    ],
    'stored XSS' => [
        'stored-xss',
        ['comment' => '<script>alert("training")</script>'],
    ],
]);

dataset('canonical normal simulation input', [
    'SQL injection authentication bypass' => [
        'sql-injection-authentication-bypass',
        ['username' => 'analyst', 'password' => 'synthetic'],
    ],
    'reflected XSS' => [
        'reflected-xss',
        ['query' => 'security basics'],
    ],
    'IDOR' => [
        'idor',
        ['resource_id' => '1001'],
    ],
    'weak password reset' => [
        'weak-password-reset',
        ['account_identifier' => 'victim@example.test', 'reset_token' => 'DIFFERENT-TOKEN'],
    ],
    'stored XSS' => [
        'stored-xss',
        ['comment' => 'Thanks for the clear explanation.'],
    ],
]);

test('canonical labs expose bounded trusted simulator definitions', function () {
    $runtime = makeSimulationLabRuntime();
    $expectedFields = [
        'sql-injection-authentication-bypass' => ['username', 'password'],
        'reflected-xss' => ['query'],
        'idor' => ['resource_id'],
        'weak-password-reset' => ['account_identifier', 'reset_token'],
        'stored-xss' => ['comment'],
    ];

    foreach ($expectedFields as $slug => $fieldKeys) {
        $definition = $runtime->definition(makeSimulationLab($slug));

        expect($definition)->not->toBeNull()
            ->and($definition['instructions'])->toBeString()
            ->and(array_column($definition['fields'], 'key'))->toBe($fieldKeys)
            ->and(collect($definition['fields'])->every(
                fn (array $field): bool => $field['max_length'] > 0
                    && in_array($field['type'], ['text', 'password'], true),
            ))->toBeTrue();
    }
});

test('canonical vulnerability conditions return a structured safe result', function (
    string $slug,
    array $input,
) {
    $result = makeSimulationLabRuntime()->simulate(makeSimulationLab($slug), $input);
    $payload = $result?->toArray();

    expect($payload)->not->toBeNull()
        ->and($payload)->toHaveKeys(['outcome', 'summary', 'input', 'steps'])
        ->and($payload['outcome'])->toBe('vulnerable_behavior')
        ->and($payload['summary'])->toBeString()
        ->and($payload['steps'])->toHaveCount(4)
        ->and(array_column($payload['steps'], 'type'))->toBe([
            'input',
            'application',
            'decision',
            'output',
        ]);
})->with('canonical vulnerable simulation input');

test('canonical ordinary inputs return normal behavior', function (
    string $slug,
    array $input,
) {
    $result = makeSimulationLabRuntime()->simulate(makeSimulationLab($slug), $input);

    expect($result?->toArray()['outcome'])->toBe('normal_behavior');
})->with('canonical normal simulation input');

test('unknown synthetic IDOR resources are rejected safely', function () {
    $result = makeSimulationLabRuntime()->simulate(
        makeSimulationLab('idor'),
        ['resource_id' => '9999'],
    );

    expect($result?->toArray()['outcome'])->toBe('rejected');
});

test('identical inert input produces the same semantic result', function () {
    $runtime = makeSimulationLabRuntime();
    $lab = makeSimulationLab('reflected-xss');
    $input = ['query' => '<script>globalThis.compromised = true</script>'];

    $first = $runtime->simulate($lab, $input)?->toArray();
    $second = $runtime->simulate($lab, $input)?->toArray();

    expect($first)->toBe($second)
        ->and($first['input']['query'])->toBe($input['query']);
});

test('unsupported labs fail closed without a fallback simulator', function () {
    $runtime = makeSimulationLabRuntime();
    $lab = makeSimulationLab('unsupported-training-lab');

    expect($runtime->definition($lab))->toBeNull()
        ->and($runtime->validationRules($lab))->toBe([])
        ->and($runtime->simulate($lab, []))->toBeNull();
});
