<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Rule\Numeric;

use KaririCode\Sanitizer\Contract\SanitizationContext;
use KaririCode\Sanitizer\Contract\SanitizationRule;
use KaririCode\Sanitizer\Core\NumericValue;

// ── Rule: ToFloatRule ────────────────────────────────────────────────
// Converts a number to a float.
// ─────────────────────────────────────────────────────────────────────

/**
 * Converts integers, finite floats and numeric strings to float.
 * Booleans, INF, NAN and non-numbers are returned unchanged.
 *
 * Preconditions: none.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['price' => ['to_float']]
 *
 * References: SPEC-002 §3.
 */
final readonly class ToFloatRule implements SanitizationRule
{
    #[\Override]
    public function sanitize(mixed $value, SanitizationContext $context): mixed
    {
        $number = NumericValue::of($value);

        return $number === null ? $value : (float) $number;
    }

    #[\Override]
    public function name(): string
    {
        return 'numeric.to_float';
    }
}
