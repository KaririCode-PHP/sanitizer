<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Rule\String;

use KaririCode\Sanitizer\Contract\SanitizationContext;
use KaririCode\Sanitizer\Contract\SanitizationRule;

// ── Rule: LowerCaseRule ──────────────────────────────────────────────
// Converts a string to lower case, in any script.
// ─────────────────────────────────────────────────────────────────────

/**
 * Lower-cases with the Unicode case mapping (mb_strtolower, UTF-8).
 *
 * Preconditions: none; other types are returned unchanged.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['email' => ['lower_case']]
 *
 * References: Unicode case mapping; SPEC-002 §1.
 */
final readonly class LowerCaseRule implements SanitizationRule
{
    #[\Override]
    public function sanitize(mixed $value, SanitizationContext $context): mixed
    {
        return is_string($value) ? mb_strtolower($value, 'UTF-8') : $value;
    }

    #[\Override]
    public function name(): string
    {
        return 'string.lower_case';
    }
}
