<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Rule\Brazilian;

use KaririCode\Sanitizer\Contract\SanitizationContext;
use KaririCode\Sanitizer\Contract\SanitizationRule;

// ── Rule: FormatCnpjRule ─────────────────────────────────────────────
// Writes a numeric or alphanumeric CNPJ in its formatted form.
// ─────────────────────────────────────────────────────────────────────

/**
 * Keeps the letters and digits of the string, upper-cases the letters,
 * and when they form a CNPJ shape (12 characters A–Z or 0–9, then 2
 * digits) writes them as AA.AAA.AAA/AAAA-00; other strings are returned
 * unchanged. It does not check the digits: validate with the cnpj rule.
 *
 * Preconditions: none; other types are returned unchanged.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['cnpj' => ['format_cnpj']]; // '12abc34501de35' → '12.ABC.345/01DE-35'
 *
 * References: IN RFB nº 2.229/2024 (alphanumeric CNPJ); ADR-008; SPEC-002 §7.
 */
final readonly class FormatCnpjRule implements SanitizationRule
{
    #[\Override]
    public function sanitize(mixed $value, SanitizationContext $context): mixed
    {
        $characters = is_string($value) ? strtoupper(preg_replace(pattern: '/[^0-9A-Za-z]/', replacement: '', subject: $value) ?? '') : '';

        return preg_match('/\A[0-9A-Z]{12}\d{2}\z/', $characters) === 1 ? vsprintf('%s%s.%s%s%s.%s%s%s/%s%s%s%s-%s%s', str_split($characters)) : $value;
    }

    #[\Override]
    public function name(): string
    {
        return 'brazilian.format_cnpj';
    }
}
