<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Rule\Numeric;

use KaririCode\Sanitizer\Contract\SanitizationContext;
use KaririCode\Sanitizer\Contract\SanitizationRule;
use KaririCode\Sanitizer\Core\NumericValue;
use KaririCode\Sanitizer\Exception\InvalidRuleException;

// ── Rule: ClampRule ──────────────────────────────────────────────────
// Limits a number to inclusive bounds.
// ─────────────────────────────────────────────────────────────────────

/**
 * Returns 'min' for numbers below it, 'max' for numbers above it, and
 * the number itself in between, as an int or float (numeric strings
 * become numbers). Non-numbers are returned unchanged.
 *
 * Preconditions: 'min' or 'max', or both, as numbers, with 'min' not above 'max'.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['quantity' => [['clamp', ['min' => 1, 'max' => 99]]]]
 *
 * References: ADR-008; SPEC-002 §3.
 */
final readonly class ClampRule implements SanitizationRule
{
    #[\Override]
    public function sanitize(mixed $value, SanitizationContext $context): mixed
    {
        [$min, $max] = self::bounds($context);
        $number = NumericValue::of($value);

        if ($number === null) {
            return $value;
        }

        return match (true) {
            $min !== null && $number < $min => $min,
            $max !== null && $number > $max => $max,
            default => $number,
        };
    }

    #[\Override]
    public function name(): string
    {
        return 'numeric.clamp';
    }

    /**
     * @return array{int|float|null, int|float|null}
     */
    private static function bounds(SanitizationContext $context): array
    {
        $min = $context->hasParameter('min') ? $context->numberParameter('min') : null;
        $max = $context->hasParameter('max') ? $context->numberParameter('max') : null;

        if ($min === null && $max === null) {
            throw InvalidRuleException::missingBounds($context->field);
        }

        if ($min !== null && $max !== null && $min > $max) {
            throw InvalidRuleException::invalidParameter(field: $context->field, parameter: 'min', expected: 'at most "max"');
        }

        return [$min, $max];
    }
}
