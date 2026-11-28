<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Rule\Type;

use KaririCode\Sanitizer\Contract\SanitizationContext;
use KaririCode\Sanitizer\Contract\SanitizationRule;

// ── Rule: ToArrayRule ────────────────────────────────────────────────
// Wraps a value in an array.
// ─────────────────────────────────────────────────────────────────────

/**
 * Returns arrays unchanged, null as an empty array, and any other value
 * as a list of one.
 *
 * Preconditions: none.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['tags' => ['to_array']]
 *
 * References: SPEC-002 §4.
 */
final readonly class ToArrayRule implements SanitizationRule
{
    #[\Override]
    public function sanitize(mixed $value, SanitizationContext $context): mixed
    {
        if (is_array($value)) {
            return $value;
        }

        return $value === null ? [] : [$value];
    }

    #[\Override]
    public function name(): string
    {
        return 'type.to_array';
    }
}
