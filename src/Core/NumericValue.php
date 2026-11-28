<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Core;

// ── Core: NumericValue ───────────────────────────────────────────────
// The number a value stands for, or null.
// ─────────────────────────────────────────────────────────────────────

/**
 * Reads a value as a number the way the numeric rules need it: integers
 * stay integers, finite floats stay floats, numeric strings ("42",
 * "-1.5", "1e3") become the int or float PHP reads them as. Booleans,
 * INF, NAN and everything else are not numbers.
 *
 * Preconditions: none.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     NumericValue::of('42');  // 42
 *     NumericValue::of(true);  // null
 *
 * References: PHP manual, numeric strings; ADR-008; SPEC-002 §3.
 */
final class NumericValue
{
    public static function of(mixed $value): int|float|null
    {
        if (is_string($value) && is_numeric($value)) {
            $value = filter_var(value: $value, filter: FILTER_VALIDATE_INT, options: FILTER_NULL_ON_FAILURE) ?? (float) $value;
        }

        if (is_int($value) || (is_float($value) && is_finite($value))) {
            return $value;
        }

        return null;
    }
}
