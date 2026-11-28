<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Rule\Type;

use KaririCode\Sanitizer\Contract\SanitizationContext;
use KaririCode\Sanitizer\Contract\SanitizationRule;
use Stringable;

// ── Rule: ToStringRule ───────────────────────────────────────────────
// Converts scalars and Stringable objects to a string.
// ─────────────────────────────────────────────────────────────────────

/**
 * Converts integers, floats and booleans ("1" and "") with PHP's string
 * conversion, and objects implementing Stringable with __toString().
 * Strings, arrays, null and other objects are returned unchanged.
 *
 * Preconditions: none.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['zip' => ['to_string']]
 *
 * References: PHP Stringable; SPEC-002 §4.
 */
final readonly class ToStringRule implements SanitizationRule
{
    #[\Override]
    public function sanitize(mixed $value, SanitizationContext $context): mixed
    {
        return is_scalar($value) || $value instanceof Stringable ? (string) $value : $value;
    }

    #[\Override]
    public function name(): string
    {
        return 'type.to_string';
    }
}
