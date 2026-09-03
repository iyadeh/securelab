<?php

use App\Models\Lab;
use App\Services\Labs\LabGuidanceProvider;

function makeGuidanceLab(string $slug): Lab
{
    $lab = new Lab;
    $lab->slug = $slug;

    return $lab;
}

dataset('canonical lab guidance', [
    'SQL injection authentication bypass' => [
        'sql-injection-authentication-bypass',
        'Parameterized database access',
    ],
    'reflected XSS' => [
        'reflected-xss',
        'Context-appropriate output encoding',
    ],
    'IDOR' => [
        'idor',
        'Object-level authorization',
    ],
    'weak password reset' => [
        'weak-password-reset',
        'Account-bound reset-token validation',
    ],
    'stored XSS' => [
        'stored-xss',
        'Safe rendering of stored content',
    ],
]);

test('canonical labs expose complete trusted guidance', function (
    string $slug,
    string $securityControl,
) {
    $guidance = (new LabGuidanceProvider)->guidanceFor(makeGuidanceLab($slug));
    $payload = $guidance?->toArray();

    expect($payload)->not->toBeNull()
        ->and(array_keys($payload))->toBe([
            'security_control',
            'root_cause',
            'impact',
            'remediation',
            'secure_behavior',
            'patterns',
        ])
        ->and($payload['security_control'])->toBe($securityControl)
        ->and($payload['root_cause'])->not->toBeEmpty()
        ->and($payload['impact'])->not->toBeEmpty()
        ->and($payload['remediation'])->not->toBeEmpty()
        ->and(collect($payload['remediation'])->every(
            fn (mixed $item): bool => is_string($item) && $item !== '',
        ))->toBeTrue()
        ->and($payload['secure_behavior'])->not->toBeEmpty()
        ->and(array_keys($payload['patterns']))->toBe(['vulnerable', 'secure'])
        ->and($payload['patterns']['vulnerable'])->not->toBeEmpty()
        ->and($payload['patterns']['secure'])->not->toBeEmpty();
})->with('canonical lab guidance');

test('guidance is deterministic for a trusted lab identity', function () {
    $provider = new LabGuidanceProvider;
    $lab = makeGuidanceLab('idor');

    $first = $provider->guidanceFor($lab)?->toArray();
    $second = $provider->guidanceFor($lab)?->toArray();

    expect($first)->toBe($second);
});

test('unsupported labs fail safely without guidance', function () {
    $guidance = (new LabGuidanceProvider)->guidanceFor(
        makeGuidanceLab('unsupported-training-lab'),
    );

    expect($guidance)->toBeNull();
});
