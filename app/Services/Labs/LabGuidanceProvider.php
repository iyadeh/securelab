<?php

namespace App\Services\Labs;

use App\Models\Lab;

final class LabGuidanceProvider
{
    public function guidanceFor(Lab $lab): ?LabGuidance
    {
        return match ($lab->slug) {
            'sql-injection-authentication-bypass' => $this->sqlInjectionAuthenticationBypass(),
            'reflected-xss' => $this->reflectedXss(),
            'idor' => $this->idor(),
            'weak-password-reset' => $this->weakPasswordReset(),
            'stored-xss' => $this->storedXss(),
            default => null,
        };
    }

    private function sqlInjectionAuthenticationBypass(): LabGuidance
    {
        return new LabGuidance(
            securityControl: 'Parameterized database access',
            rootCause: 'The fictional login mixes user-controlled values with authentication query structure, allowing data to influence the condition being evaluated.',
            impact: 'The fictional application could authenticate an unverified user and expose the account data and privileges available to that session.',
            remediation: [
                'Use prepared or parameterized database operations for every credential lookup.',
                'Keep query structure fixed and bind submitted values only as data.',
                'Use the authentication system to verify password hashes, return generic failures, and limit database privileges.',
            ],
            secureBehavior: 'A protected login would treat every submitted value as data. It could not change the authentication condition and invalid credentials would remain rejected.',
            patterns: [
                'vulnerable' => <<<'PSEUDOCODE'
condition = queryStructure + username + password
authenticate(condition)
PSEUDOCODE,
                'secure' => <<<'PSEUDOCODE'
statement = prepare(authenticationQuery)
statement.bind(username)
account = statement.readOne()
authenticate(verifyPassword(password, account.passwordHash))
PSEUDOCODE,
            ],
        );
    }

    private function reflectedXss(): LabGuidance
    {
        return new LabGuidance(
            securityControl: 'Context-appropriate output encoding',
            rootCause: 'The fictional search page places learner-controlled content into an HTML response without encoding it for that output context.',
            impact: 'A browser would interpret attacker-controlled markup in the fictional page, potentially exposing data or actions available to the affected session.',
            remediation: [
                'Use framework escaping by default and encode untrusted values for their exact output context.',
                'Keep untrusted values out of executable HTML, script, style, and URL sinks.',
                'Sanitize only when intentionally supporting restricted rich HTML; do not treat it as a universal replacement for output encoding.',
            ],
            secureBehavior: 'A protected search page would encode markup-looking input and display it as inert text instead of executable browser content.',
            patterns: [
                'vulnerable' => <<<'PSEUDOCODE'
renderHtml(searchInput)
PSEUDOCODE,
                'secure' => <<<'PSEUDOCODE'
safeText = encodeForHtml(searchInput)
renderHtmlTemplate(safeText)
PSEUDOCODE,
            ],
        );
    }

    private function idor(): LabGuidance
    {
        return new LabGuidance(
            securityControl: 'Object-level authorization',
            rootCause: 'The fictional endpoint checks whether a resource exists but not whether the current user is authorized to access it.',
            impact: 'The fictional application could disclose or change another account\'s resource when its identifier is requested.',
            remediation: [
                'Authorize every object read and write on the server.',
                'Resolve resources within the authenticated user\'s permitted scope where practical.',
                'Deny access by default and never treat an unguessable identifier as authorization.',
            ],
            secureBehavior: 'A protected endpoint would deny a request for another account\'s resource even when that resource exists and its identifier is known.',
            patterns: [
                'vulnerable' => <<<'PSEUDOCODE'
resource = find(requestedId)
return resource
PSEUDOCODE,
                'secure' => <<<'PSEUDOCODE'
resource = find(requestedId)
authorize(currentUser, resource)
return resource
PSEUDOCODE,
            ],
        );
    }

    private function weakPasswordReset(): LabGuidance
    {
        return new LabGuidance(
            securityControl: 'Account-bound reset-token validation',
            rootCause: 'The fictional recovery workflow accepts a predictable token and does not provide strong, one-time proof that the reset belongs to the intended account.',
            impact: 'The fictional application could allow an unauthorized password change and account takeover.',
            remediation: [
                'Generate cryptographically strong random reset tokens and store them securely.',
                'Bind each token to one account, enforce a short expiration, and allow one use only.',
                'Invalidate the token after success, rate-limit requests, and avoid revealing sensitive account state.',
            ],
            secureBehavior: 'A protected recovery workflow would reject predictable, expired, reused, or account-mismatched tokens without changing any real or fictional account.',
            patterns: [
                'vulnerable' => <<<'PSEUDOCODE'
if submittedToken == predictableToken
    allowReset(account)
PSEUDOCODE,
                'secure' => <<<'PSEUDOCODE'
token = verifyOneTimeToken(account, submittedToken, expiresAt)
allowReset(token.account)
invalidate(token)
PSEUDOCODE,
            ],
        );
    }

    private function storedXss(): LabGuidance
    {
        return new LabGuidance(
            securityControl: 'Safe rendering of stored content',
            rootCause: 'The fictional application stores learner-controlled content and later treats it as trusted HTML instead of untrusted data at every rendering boundary.',
            impact: 'A browser would interpret attacker-controlled markup for later fictional viewers, potentially affecting every session that opens the stored content.',
            remediation: [
                'Treat stored user content as untrusted every time it is rendered.',
                'Use context-appropriate escaping and avoid unsafe HTML or script sinks.',
                'When rich HTML is required, sanitize against a strict allowlist and keep it out of other unsafe output contexts.',
            ],
            secureBehavior: 'A protected application would store the submitted text as data and render it inertly for every later viewer.',
            patterns: [
                'vulnerable' => <<<'PSEUDOCODE'
save(comment)
renderAsHtml(load(comment))
PSEUDOCODE,
                'secure' => <<<'PSEUDOCODE'
save(comment)
safeText = encodeForOutputContext(load(comment))
renderHtmlTemplate(safeText)
PSEUDOCODE,
            ],
        );
    }
}
