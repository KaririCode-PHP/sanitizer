<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Rule\String;

use KaririCode\Sanitizer\Contract\SanitizationContext;
use KaririCode\Sanitizer\Contract\SanitizationRule;

// ── Rule: TrimRule ───────────────────────────────────────────────────
// Removes characters from both ends of a string.
// ─────────────────────────────────────────────────────────────────────

/**
 * Trims whitespace (space, tab, line feed, carriage return, NUL,
 * vertical tab) or the 'characters' given, with PHP's trim() syntax
 * ("a..z" ranges included).
 *
 * Preconditions: 'characters', when given, is a string.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['email' => ['trim'], 'code' => [['trim', ['characters' => '0']]]]
 *
 * References: PHP trim(); SPEC-002 §1.
 */
final readonly class TrimRule implements SanitizationRule
{
    #[\Override]
    public function sanitize(mixed $value, SanitizationContext $context): mixed
    {
        $characters = $context->stringParameter('characters', " \t\n\r\0\x0B");

        return is_string($value) ? trim($value, $characters) : $value;
    }

    #[\Override]
    public function name(): string
    {
        return 'string.trim';
    }
}
