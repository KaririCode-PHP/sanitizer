<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Rule\String;

use KaririCode\Sanitizer\Contract\SanitizationContext;
use KaririCode\Sanitizer\Contract\SanitizationRule;

// ── Rule: UpperCaseRule ──────────────────────────────────────────────
// Converts a string to upper case, in any script.
// ─────────────────────────────────────────────────────────────────────

/**
 * Upper-cases with the Unicode case mapping (mb_strtoupper, UTF-8).
 *
 * Preconditions: none; other types are returned unchanged.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['state' => ['upper_case']]
 *
 * References: Unicode case mapping; SPEC-002 §1.
 */
final readonly class UpperCaseRule implements SanitizationRule
{
    #[\Override]
    public function sanitize(mixed $value, SanitizationContext $context): mixed
    {
        return is_string($value) ? mb_strtoupper($value, 'UTF-8') : $value;
    }

    #[\Override]
    public function name(): string
    {
        return 'string.upper_case';
    }
}
