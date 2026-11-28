<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Rule\Filter;

use KaririCode\Sanitizer\Contract\SanitizationContext;
use KaririCode\Sanitizer\Contract\SanitizationRule;

// ── Rule: DigitsOnlyRule ─────────────────────────────────────────────
// Keeps only the ASCII digits of a string.
// ─────────────────────────────────────────────────────────────────────

/**
 * Removes every character that is not 0–9: "(88) 99999-0000" becomes
 * "88999990000".
 *
 * Preconditions: none; other types are returned unchanged.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['phone' => ['digits_only']]
 *
 * References: SPEC-002 §6.
 */
final readonly class DigitsOnlyRule implements SanitizationRule
{
    #[\Override]
    public function sanitize(mixed $value, SanitizationContext $context): mixed
    {
        return is_string($value) ? preg_replace(pattern: '/\D/', replacement: '', subject: $value) ?? '' : $value;
    }

    #[\Override]
    public function name(): string
    {
        return 'filter.digits_only';
    }
}
