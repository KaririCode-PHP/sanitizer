<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Rule\Date;

use DateTimeImmutable;
use DateTimeZone;
use Exception;
use KaririCode\Sanitizer\Contract\SanitizationContext;
use KaririCode\Sanitizer\Contract\SanitizationRule;
use KaririCode\Sanitizer\Core\NumericValue;
use KaririCode\Sanitizer\Exception\InvalidRuleException;

// ── Rule: TimestampToDateRule ────────────────────────────────────────
// Writes a Unix timestamp as a date.
// ─────────────────────────────────────────────────────────────────────

/**
 * Reads numbers and numeric strings as seconds since the Unix epoch (a
 * fraction is kept as microseconds, which 'u' shows) and writes them in
 * 'format' (Y-m-d H:i:s unless set) in 'timezone' (UTC unless set).
 * Non-numbers and timestamps outside the years 1 to 9999 are returned
 * unchanged.
 *
 * Preconditions: 'format' is a date format; 'timezone' is an identifier DateTimeZone accepts.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['created_at' => [['timestamp_to_date', ['timezone' => 'America/Fortaleza']]]]
 *
 * References: IANA time zone database; PHP DateTimeZone; ADR-008; SPEC-002 §5.
 */
final readonly class TimestampToDateRule implements SanitizationRule
{
    #[\Override]
    public function sanitize(mixed $value, SanitizationContext $context): mixed
    {
        $format = $context->stringParameter('format', 'Y-m-d H:i:s');
        $zone = self::zone($context);
        $number = NumericValue::of($value);

        if ($number === null || $number < self::FIRST || $number >= self::AFTER_LAST) {
            return $value;
        }

        return DateTimeImmutable::createFromTimestamp($number)->setTimezone($zone)->format($format);
    }

    #[\Override]
    public function name(): string
    {
        return 'date.timestamp_to_date';
    }

    /** 0001-01-01T00:00:00Z */
    private const int FIRST = -62135596800;

    /** 10000-01-01T00:00:00Z */
    private const int AFTER_LAST = 253402300800;

    private static function zone(SanitizationContext $context): DateTimeZone
    {
        $timezone = $context->stringParameter('timezone', 'UTC');

        if ($timezone === '') {
            throw InvalidRuleException::invalidParameter(field: $context->field, parameter: 'timezone', expected: 'a time zone identifier');
        }

        try {
            return new DateTimeZone($timezone);
        } catch (Exception) {
            throw InvalidRuleException::invalidParameter(field: $context->field, parameter: 'timezone', expected: 'a time zone identifier', given: var_export($timezone, true));
        }
    }
}
