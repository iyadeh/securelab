<?php

namespace Database\Seeders;

use App\Models\Lab;
use Illuminate\Database\Seeder;

class LabSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /** @var list<array{slug: string, title: string, description: string, vulnerability_type: string, difficulty: string, scenario: string}> $labs */
        $labs = [
            [
                'slug' => 'sql-injection-authentication-bypass',
                'title' => 'SQL Injection Authentication Bypass',
                'description' => 'Explore how unsafe query construction can turn untrusted login input into an authentication decision.',
                'vulnerability_type' => 'sql_injection',
                'difficulty' => 'easy',
                'scenario' => 'A simulated login form combines submitted credentials with an unsafe database lookup. The evaluator models an authentication bypass without executing SQL.',
            ],
            [
                'slug' => 'reflected-xss',
                'title' => 'Reflected XSS',
                'description' => 'See how reflected input becomes active browser content when output is not encoded for its HTML context.',
                'vulnerability_type' => 'cross_site_scripting',
                'difficulty' => 'easy',
                'scenario' => 'A simulated search page reflects a query parameter into its result heading without context-aware output encoding. No JavaScript is executed.',
            ],
            [
                'slug' => 'idor',
                'title' => 'IDOR',
                'description' => 'Learn how missing object-level authorization can expose another user\'s data through a predictable identifier.',
                'vulnerability_type' => 'access_control',
                'difficulty' => 'easy',
                'scenario' => 'A simulated profile endpoint loads records from a user-controlled identifier but omits the ownership check.',
            ],
            [
                'slug' => 'weak-password-reset',
                'title' => 'Weak Password Reset',
                'description' => 'Examine how weak reset tokens and incomplete account verification can expose user accounts.',
                'vulnerability_type' => 'authentication',
                'difficulty' => 'medium',
                'scenario' => 'A simulated recovery workflow applies weak reset-token and account-verification rules without contacting an email provider or external service.',
            ],
            [
                'slug' => 'stored-xss',
                'title' => 'Stored XSS',
                'description' => 'Understand how persisted untrusted content can affect every user who later views it.',
                'vulnerability_type' => 'cross_site_scripting',
                'difficulty' => 'medium',
                'scenario' => 'A simulated comment form stores submitted content and later renders it without context-aware output encoding. No JavaScript is executed.',
            ],
        ];

        foreach ($labs as $lab) {
            Lab::query()->updateOrCreate(
                ['slug' => $lab['slug']],
                $lab,
            );
        }
    }
}
