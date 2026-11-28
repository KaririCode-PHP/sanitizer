<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Rule\Numeric;

use KaririCode\Sanitizer\Contract\SanitizationContext;
use KaririCode\Sanitizer\Contract\SanitizationRule;
use KaririCode\Sanitizer\Core\NumericValue;

// ── Rule: ToIntRule ──────────────────────────────────────────────────
// Converts a number to an integer when nothing is lost.
// ─────────────────────────────────────────────────────────────────────

/**
 * Converts integral numbers ("42", 42.0, "1e3") to int. Fractions
 * ("12.5"), values beyond the integer range and non-numbers are returned
 * unchanged, so a validation rule can still reject them instead of the
 * sanitizer rounding them silently.
 *
 * Preconditions: none.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['quantity' => ['trim', 'to_int']]
 *
 * References: ADR-008; SPEC-002 §3.
 */
final readonly class ToIntRule implements SanitizationRule
{
    #[\Override]
    public function sanitize(mixed $value, SanitizationContext $context): mixed
    {
        $number = NumericValue::of($value);

        if (is_float($number) && fmod($number, 1.0) === 0.0 && $number >= -9.2233720368547758E18 && $number < 9.2233720368547758E18) {
            return (int) $number;
        }

        return is_int($number) ? $number : $value;
    }

    #[\Override]
    public function name(): string
    {
        return 'numeric.to_int';
    }
}
