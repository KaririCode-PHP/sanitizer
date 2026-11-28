<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Rule\Filter;

use KaririCode\Sanitizer\Contract\SanitizationContext;
use KaririCode\Sanitizer\Contract\SanitizationRule;

// ── Rule: EmailFilterRule ────────────────────────────────────────────
// Trims, lower-cases and filters an email address.
// ─────────────────────────────────────────────────────────────────────

/**
 * Lower-cases (the mailbox part too, as most providers treat it) and
 * removes the characters FILTER_SANITIZE_EMAIL removes: whitespace,
 * anywhere, and everything outside printable ASCII, so "josé@x.com"
 * becomes "jos@x.com". The result is not checked: validate it with the
 * email rule, and keep internationalized addresses away from this rule.
 *
 * Preconditions: none; other types are returned unchanged.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['email' => ['email_filter']]
 *
 * References: PHP FILTER_SANITIZE_EMAIL; RFC 5321 §2.4; SPEC-002 §6.
 */
final readonly class EmailFilterRule implements SanitizationRule
{
    #[\Override]
    public function sanitize(mixed $value, SanitizationContext $context): mixed
    {
        return is_string($value) ? filter_var(strtolower($value), FILTER_SANITIZE_EMAIL) : $value;
    }

    #[\Override]
    public function name(): string
    {
        return 'filter.email';
    }
}
