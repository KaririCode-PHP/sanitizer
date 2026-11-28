<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Rule\Filter;

use KaririCode\Sanitizer\Contract\SanitizationContext;
use KaririCode\Sanitizer\Contract\SanitizationRule;

// ── Rule: AlphanumericOnlyRule ───────────────────────────────────────
// Keeps only the letters and digits of a string, in any script.
// ─────────────────────────────────────────────────────────────────────

/**
 * Removes every character that is not a Unicode letter or number.
 * Invalid UTF-8 bytes are removed too.
 *
 * Preconditions: none; other types are returned unchanged.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['username' => ['alphanumeric_only']]
 *
 * References: Unicode TR #18 (properties L and N); SPEC-002 §6.
 */
final readonly class AlphanumericOnlyRule implements SanitizationRule
{
    #[\Override]
    public function sanitize(mixed $value, SanitizationContext $context): mixed
    {
        return is_string($value) ? preg_replace(pattern: '/[^\pL\pN]/u', replacement: '', subject: mb_scrub($value, 'UTF-8')) ?? '' : $value;
    }

    #[\Override]
    public function name(): string
    {
        return 'filter.alphanumeric_only';
    }
}
