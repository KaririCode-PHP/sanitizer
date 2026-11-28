<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Rule\Brazilian;

use KaririCode\Sanitizer\Contract\SanitizationContext;
use KaririCode\Sanitizer\Contract\SanitizationRule;

// ── Rule: FormatCpfRule ──────────────────────────────────────────────
// Writes 11 digits as a formatted CPF.
// ─────────────────────────────────────────────────────────────────────

/**
 * Keeps the digits of the string and, when there are exactly 11, writes
 * them as 000.000.000-00; other strings are returned unchanged. It does
 * not check the digits: validate with the cpf rule.
 *
 * Preconditions: none; other types are returned unchanged.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['cpf' => ['format_cpf']]
 *
 * References: Receita Federal do Brasil, CPF; SPEC-002 §7.
 */
final readonly class FormatCpfRule implements SanitizationRule
{
    #[\Override]
    public function sanitize(mixed $value, SanitizationContext $context): mixed
    {
        $digits = is_string($value) ? preg_replace(pattern: '/\D/', replacement: '', subject: $value) ?? '' : '';

        return strlen($digits) === 11 ? vsprintf('%s%s%s.%s%s%s.%s%s%s-%s%s', str_split($digits)) : $value;
    }

    #[\Override]
    public function name(): string
    {
        return 'brazilian.format_cpf';
    }
}
