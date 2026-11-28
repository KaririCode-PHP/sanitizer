<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Core;

use DateTimeImmutable;

// ── Core: DateValue ──────────────────────────────────────────────────
// Parses a date in a format, without rolling invalid dates over.
// ─────────────────────────────────────────────────────────────────────

/**
 * Reads a date written in a DateTime format, strictly: fields the format
 * does not name are zero instead of the current time, and a date PHP
 * would otherwise move (31/02, hour 25) is not a date, so a rule leaves
 * it as it was instead of rewriting it as another day.
 *
 * Preconditions: the format uses DateTimeImmutable::createFromFormat()
 * letters.
 *
 * Thread-safety: stateless; it reads the last parse errors right after
 * its own call.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     DateValue::parse('31/02/2026', 'd/m/Y'); // null
 *
 * References: PHP manual, DateTimeImmutable::createFromFormat(); ADR-008; SPEC-002 §5.
 */
final class DateValue
{
    public static function parse(string $value, string $format): ?DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!' . $format, $value);

        return $date !== false && DateTimeImmutable::getLastErrors() === false ? $date : null;
    }
}
