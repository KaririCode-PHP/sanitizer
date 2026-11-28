<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Rule\Brazilian;

use KaririCode\Sanitizer\Contract\SanitizationContext;
use KaririCode\Sanitizer\Contract\SanitizationRule;

// ── Rule: FormatCepRule ──────────────────────────────────────────────
// Writes 8 digits as a formatted CEP.
// ─────────────────────────────────────────────────────────────────────

/**
 * Keeps the digits of the string and, when there are exactly 8, writes
 * them as 00000-000; other strings are returned unchanged.
 *
 * Preconditions: none; other types are returned unchanged.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['cep' => ['format_cep']]
 *
 * References: Correios, CEP; SPEC-002 §7.
 */
final readonly class FormatCepRule implements SanitizationRule
{
    #[\Override]
    public function sanitize(mixed $value, SanitizationContext $context): mixed
    {
        $digits = is_string($value) ? preg_replace(pattern: '/\D/', replacement: '', subject: $value) ?? '' : '';

        return strlen($digits) === 8 ? vsprintf('%s%s%s%s%s-%s%s%s', str_split($digits)) : $value;
    }

    #[\Override]
    public function name(): string
    {
        return 'brazilian.format_cep';
    }
}
