<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Rule\String;

use KaririCode\Sanitizer\Contract\SanitizationContext;
use KaririCode\Sanitizer\Contract\SanitizationRule;

// ── Rule: CapitalizeRule ─────────────────────────────────────────────
// Capitalizes every word of a string.
// ─────────────────────────────────────────────────────────────────────

/**
 * Title-cases with the Unicode mapping (MB_CASE_TITLE): the first letter
 * of each word upper case, the others lower case, so "josé da silva"
 * becomes "José Da Silva" and "JOSÉ McDONALD" becomes "José Mcdonald".
 *
 * Preconditions: none; other types are returned unchanged.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['name' => ['trim', 'capitalize']]
 *
 * References: Unicode case mapping; SPEC-002 §1.
 */
final readonly class CapitalizeRule implements SanitizationRule
{
    #[\Override]
    public function sanitize(mixed $value, SanitizationContext $context): mixed
    {
        return is_string($value) ? mb_convert_case(string: $value, mode: MB_CASE_TITLE, encoding: 'UTF-8') : $value;
    }

    #[\Override]
    public function name(): string
    {
        return 'string.capitalize';
    }
}
