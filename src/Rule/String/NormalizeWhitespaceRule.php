<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Rule\String;

use KaririCode\Sanitizer\Contract\SanitizationContext;
use KaririCode\Sanitizer\Contract\SanitizationRule;

// ── Rule: NormalizeWhitespaceRule ────────────────────────────────────
// Collapses whitespace to single spaces and trims.
// ─────────────────────────────────────────────────────────────────────

/**
 * Replaces every run of whitespace, Unicode spaces such as the no-break
 * space included, with one space and trims the ends. Invalid UTF-8 bytes
 * become "?" first, so the result is always valid UTF-8.
 *
 * Preconditions: none; other types are returned unchanged.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['name' => ['normalize_whitespace']]
 *
 * References: Unicode White_Space property; SPEC-002 §1.
 */
final readonly class NormalizeWhitespaceRule implements SanitizationRule
{
    #[\Override]
    public function sanitize(mixed $value, SanitizationContext $context): mixed
    {
        return is_string($value) ? trim(preg_replace(pattern: '/\s+/u', replacement: ' ', subject: mb_scrub($value, 'UTF-8')) ?? '') : $value;
    }

    #[\Override]
    public function name(): string
    {
        return 'string.normalize_whitespace';
    }
}
