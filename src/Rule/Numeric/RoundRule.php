<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Rule\Numeric;

use KaririCode\Sanitizer\Contract\SanitizationContext;
use KaririCode\Sanitizer\Contract\SanitizationRule;
use KaririCode\Sanitizer\Core\NumericValue;
use KaririCode\Sanitizer\Exception\InvalidRuleException;
use RoundingMode;

// ── Rule: RoundRule ──────────────────────────────────────────────────
// Rounds a number to a precision.
// ─────────────────────────────────────────────────────────────────────

/**
 * Rounds to 'precision' decimal places (2 unless set; negative rounds to
 * tens, hundreds) with the 'mode' given: round (half away from zero,
 * the default), ceil (towards +∞) or floor (towards −∞). The result is
 * a float; PHP 8.4's rounding modes keep 1.1 from ceiling to 1.11.
 * Non-numbers are returned unchanged.
 *
 * Preconditions: 'precision' is an integer; 'mode' is round, ceil or floor.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['price' => [['round', ['precision' => 2, 'mode' => 'ceil']]]]
 *
 * References: PHP round() and RoundingMode; SPEC-002 §3.
 */
final readonly class RoundRule implements SanitizationRule
{
    #[\Override]
    public function sanitize(mixed $value, SanitizationContext $context): mixed
    {
        $precision = $context->intParameter('precision', 2);
        $mode = match ($context->stringParameter('mode', 'round')) {
            'round' => RoundingMode::HalfAwayFromZero,
            'ceil' => RoundingMode::PositiveInfinity,
            'floor' => RoundingMode::NegativeInfinity,
            default => throw InvalidRuleException::invalidParameter(field: $context->field, parameter: 'mode', expected: 'round, ceil or floor'),
        };
        $number = NumericValue::of($value);

        return $number === null ? $value : round(num: $number, precision: $precision, mode: $mode);
    }

    #[\Override]
    public function name(): string
    {
        return 'numeric.round';
    }
}
