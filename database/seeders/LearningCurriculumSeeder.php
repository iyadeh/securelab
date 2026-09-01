<?php

namespace Database\Seeders;

use App\Models\Lab;
use App\Models\LearningModule;
use App\Models\LearningPath;
use App\Models\Lesson;
use Illuminate\Database\Seeder;

class LearningCurriculumSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $learningPath = LearningPath::query()->updateOrCreate(
            ['slug' => 'web-security-fundamentals'],
            [
                'title' => 'Web Security Fundamentals',
                'description' => 'Learn foundational web security by tracing unsafe input, broken authorization, and insecure account workflows from root cause to remediation.',
                'difficulty' => 'easy',
            ],
        );

        /** @var list<array{title: string, description: string, position: int, lessons: list<array{lab_slug: string, slug: string, title: string, summary: string, content: string, estimated_minutes: int, position: int}>}> $modules */
        $modules = [
            [
                'title' => 'Injection',
                'description' => 'Learn how untrusted input can change server-side data operations and how parameterization preserves intended query structure.',
                'position' => 1,
                'lessons' => [
                    [
                        'lab_slug' => 'sql-injection-authentication-bypass',
                        'slug' => 'sql-injection-authentication-bypass',
                        'title' => 'SQL Injection Authentication Bypass',
                        'summary' => 'Understand how unsafe query construction can turn login input into an authentication bypass.',
                        'content' => <<<'MARKDOWN'
## Learn

SQL injection occurs when an application lets untrusted input change the meaning of a database query. In authentication code, that mistake can turn submitted credentials into query instructions instead of plain data.

## Understand

A vulnerable login flow builds a query by joining strings. The database cannot distinguish developer-written query structure from attacker-controlled input, so the resulting condition may no longer represent the intended username and password check.

## Exploit

An attacker aims to influence the authentication decision without providing valid credentials. SecureLab models that reasoning through a controlled evaluator; it never sends learner input to a real SQL engine.

## Analyze

Trace the username and password from the HTTP request into query construction, the database condition, and the final login response. A successful bypass can expose user accounts and any data or privileges available to them.

## Fix

Use parameterized queries so values remain data. Locate the account through trusted query structure, verify password hashes with the authentication system, limit database privileges, return generic failures, and monitor repeated login abuse.
MARKDOWN,
                        'estimated_minutes' => 20,
                        'position' => 1,
                    ],
                ],
            ],
            [
                'title' => 'Cross-Site Scripting',
                'description' => 'Learn how unsafe output handling lets untrusted content cross from application data into browser interpretation.',
                'position' => 2,
                'lessons' => [
                    [
                        'lab_slug' => 'reflected-xss',
                        'slug' => 'reflected-xss',
                        'title' => 'Reflected XSS',
                        'summary' => 'Understand how reflected input becomes active browser content when output encoding is missing.',
                        'content' => <<<'MARKDOWN'
## Learn

Reflected cross-site scripting occurs when request input is immediately returned in a response and the browser interprets it as page content instead of text.

## Understand

A vulnerable search page places the submitted term directly into HTML. The server may treat the value as harmless text, but the browser applies rules from the output context and can interpret unsafe markup or script-capable content.

## Exploit

An attacker aims to make a victim open a crafted request whose reflected value changes the rendered page. SecureLab simulates the reflection and browser decision without executing JavaScript.

## Analyze

Follow the value from the URL into the response template and browser parser. Impact can include misleading content, actions performed in a victim's session, or exposure of data available to the page.

## Fix

Use framework escaping and context-aware output encoding at every rendering boundary. Keep untrusted values out of dangerous HTML contexts, validate expected input shape, and use Content Security Policy as defense in depth rather than the primary fix.
MARKDOWN,
                        'estimated_minutes' => 15,
                        'position' => 1,
                    ],
                    [
                        'lab_slug' => 'stored-xss',
                        'slug' => 'stored-xss',
                        'title' => 'Stored XSS',
                        'summary' => 'Understand how persisted untrusted content can affect every later viewer.',
                        'content' => <<<'MARKDOWN'
## Learn

Stored cross-site scripting begins when untrusted content is saved and later rendered for one or more users. Persistence makes the vulnerable display path repeat the risk without another crafted request.

## Understand

A vulnerable comment feature stores submitted text correctly but renders it as trusted HTML later. Storage does not make input safe; security depends on how each output context handles the value.

## Exploit

An attacker aims to save content that changes the page when another user views it. SecureLab models storage and rendering decisions while preventing arbitrary JavaScript execution.

## Analyze

Trace the value from submission through persistence and into every affected view. Impact grows with the audience and privileges of viewers, especially when moderators or administrators open the stored content.

## Fix

Encode output for its exact context, keep rich-text features on a strict allowlist when they are required, and avoid rendering raw user content. Apply Content Security Policy as an additional boundary and review every view that reuses stored data.
MARKDOWN,
                        'estimated_minutes' => 20,
                        'position' => 2,
                    ],
                ],
            ],
            [
                'title' => 'Access & Account Security',
                'description' => 'Learn how object authorization and secure recovery workflows protect resources and accounts after authentication.',
                'position' => 3,
                'lessons' => [
                    [
                        'lab_slug' => 'idor',
                        'slug' => 'idor',
                        'title' => 'IDOR',
                        'summary' => 'Understand why object identifiers never replace server-side ownership checks.',
                        'content' => <<<'MARKDOWN'
## Learn

Insecure Direct Object Reference is a broken access-control flaw. It appears when an application accepts an object identifier and returns or changes that object without verifying whether the current user is allowed to act on it.

## Understand

Authentication answers who the user is; authorization decides which records that user may access. A profile endpoint remains vulnerable when it loads a valid identifier but skips the ownership or permission check.

## Exploit

An attacker aims to substitute another object's identifier and observe whether the server trusts it. SecureLab evaluates the authorization decision against simulated records without exposing real user data.

## Analyze

Trace the authenticated user, requested identifier, loaded object, and missing policy decision. Impact may include reading private records, changing another user's data, or performing privileged actions.

## Fix

Authorize every object-level action on the server. Scope queries to resources available to the current user, apply policies consistently, deny access by default, and test access using users who own and do not own the target object.
MARKDOWN,
                        'estimated_minutes' => 15,
                        'position' => 1,
                    ],
                    [
                        'lab_slug' => 'weak-password-reset',
                        'slug' => 'weak-password-reset',
                        'title' => 'Weak Password Reset',
                        'summary' => 'Understand how weak reset tokens and incomplete verification can lead to account takeover.',
                        'content' => <<<'MARKDOWN'
## Learn

Password recovery becomes vulnerable when reset tokens are predictable, reusable, long-lived, exposed, or not bound to the intended account. A flaw in this workflow can bypass otherwise strong login security.

## Understand

A secure reset proves control of a short-lived secret and applies the password change to exactly one account. Weak workflows may trust user-supplied account data, reveal whether an account exists, or accept a token after it should be invalid.

## Exploit

An attacker aims to obtain or predict a valid reset state and use it for another account. SecureLab models token and account checks without sending email or contacting external services.

## Analyze

Follow the reset request, token creation, delivery assumption, validation, password update, and token invalidation. Failure can result in full account takeover and persistent access through existing sessions.

## Fix

Generate cryptographically random tokens, store them securely, bind them to one account, enforce short expiration and single use, rate-limit requests, avoid account enumeration, and invalidate relevant sessions after a successful reset.
MARKDOWN,
                        'estimated_minutes' => 20,
                        'position' => 2,
                    ],
                ],
            ],
        ];

        foreach ($modules as $module) {
            $learningModule = LearningModule::query()->updateOrCreate(
                [
                    'learning_path_id' => $learningPath->id,
                    'position' => $module['position'],
                ],
                [
                    'title' => $module['title'],
                    'description' => $module['description'],
                ],
            );

            foreach ($module['lessons'] as $lesson) {
                $lab = Lab::query()->where('slug', $lesson['lab_slug'])->firstOrFail();

                Lesson::query()->updateOrCreate(
                    ['slug' => $lesson['slug']],
                    [
                        'learning_module_id' => $learningModule->id,
                        'lab_id' => $lab->id,
                        'title' => $lesson['title'],
                        'summary' => $lesson['summary'],
                        'content' => $lesson['content'],
                        'estimated_minutes' => $lesson['estimated_minutes'],
                        'position' => $lesson['position'],
                    ],
                );
            }
        }
    }
}
