<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Rule\Filter;

use KaririCode\Sanitizer\Contract\SanitizationContext;
use KaririCode\Sanitizer\Contract\SanitizationRule;

// ── Rule: AlphaOnlyRule ──────────────────────────────────────────────
// Keeps only the letters of a string, in any script.
// ─────────────────────────────────────────────────────────────────────

/**
 * Removes every character that is not a Unicode letter. Invalid UTF-8
 * bytes are removed too.
 *
 * Preconditions: none; other types are returned unchanged.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['name' => ['alpha_only']]
 *
 * References: Unicode TR #18 (property L); SPEC-002 §6.
 */
final readonly class AlphaOnlyRule implements SanitizationRule
{
    #[\Override]
    public function sanitize(mixed $value, SanitizationContext $context): mixed
    {
        return is_string($value) ? preg_replace(pattern: '/[^\pL]/u', replacement: '', subject: mb_scrub($value, 'UTF-8')) ?? '' : $value;
    }

    #[\Override]
    public function name(): string
    {
        return 'filter.alpha_only';
    }
}
