<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Rule\Type;

use KaririCode\Sanitizer\Contract\SanitizationContext;
use KaririCode\Sanitizer\Contract\SanitizationRule;
use KaririCode\Sanitizer\Core\NumericValue;

// ── Rule: ToBoolRule ─────────────────────────────────────────────────
// Converts boolean-like values to a boolean.
// ─────────────────────────────────────────────────────────────────────

/**
 * Reads "1", "true", "yes", "on" as true and "0", "false", "no", "off"
 * and "" as false, ignoring case and surrounding whitespace; numbers are
 * true unless zero. Other strings and other types are returned
 * unchanged.
 *
 * Preconditions: none.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['newsletter' => ['to_bool']]
 *
 * References: SPEC-002 §4.
 */
final readonly class ToBoolRule implements SanitizationRule
{
    #[\Override]
    public function sanitize(mixed $value, SanitizationContext $context): mixed
    {
        if (is_string($value)) {
            return match (strtolower(trim($value))) {
                '1', 'true', 'yes', 'on' => true,
                '0', 'false', 'no', 'off', '' => false,
                default => $value,
            };
        }

        $number = NumericValue::of($value);

        return $number === null ? $value : $number !== 0 && $number !== 0.0;
    }

    #[\Override]
    public function name(): string
    {
        return 'type.to_bool';
    }
}
