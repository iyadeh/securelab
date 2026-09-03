<?php

namespace App\Services\Labs;

final readonly class LabGuidance
{
    /**
     * @param  list<string>  $remediation
     * @param  array{vulnerable: string, secure: string}|null  $patterns
     */
    public function __construct(
        public string $securityControl,
        public string $rootCause,
        public string $impact,
        public array $remediation,
        public string $secureBehavior,
        public ?array $patterns,
    ) {}

    /**
     * @return array{
     *     security_control: string,
     *     root_cause: string,
     *     impact: string,
     *     remediation: list<string>,
     *     secure_behavior: string,
     *     patterns: array{vulnerable: string, secure: string}|null
     * }
     */
    public function toArray(): array
    {
        return [
            'security_control' => $this->securityControl,
            'root_cause' => $this->rootCause,
            'impact' => $this->impact,
            'remediation' => $this->remediation,
            'secure_behavior' => $this->secureBehavior,
            'patterns' => $this->patterns,
        ];
    }
}
