<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Rule\Date;

use KaririCode\Sanitizer\Contract\SanitizationContext;
use KaririCode\Sanitizer\Contract\SanitizationRule;
use KaririCode\Sanitizer\Core\DateValue;

// ── Rule: NormalizeDateRule ──────────────────────────────────────────
// Rewrites a date from one format into another.
// ─────────────────────────────────────────────────────────────────────

/**
 * Parses the string strictly in 'from' (d/m/Y unless set) and writes it
 * in 'to' (Y-m-d unless set). Strings that are not a real date in
 * 'from' (31/02/2026, extra characters) are returned unchanged, so
 * validation can reject them.
 *
 * Preconditions: 'from' and 'to', when given, use DateTimeImmutable::createFromFormat() letters.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['birthday' => [['normalize_date', ['from' => 'd/m/Y', 'to' => 'Y-m-d']]]]
 *
 * References: ISO 8601-1:2019 (the default output); ADR-008; SPEC-002 §5.
 */
final readonly class NormalizeDateRule implements SanitizationRule
{
    #[\Override]
    public function sanitize(mixed $value, SanitizationContext $context): mixed
    {
        $from = $context->stringParameter('from', 'd/m/Y');
        $to = $context->stringParameter('to', 'Y-m-d');

        if (!is_string($value)) {
            return $value;
        }

        return DateValue::parse($value, $from)?->format($to) ?? $value;
    }

    #[\Override]
    public function name(): string
    {
        return 'date.normalize';
    }
}
