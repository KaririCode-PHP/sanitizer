<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Exception;

use KaririCode\Exception\ValidationException as ValidationBase;

// ── Exception: UnsupportedInputException ─────────────────────────────
// The processor received something other than an array (code 3340).
// ─────────────────────────────────────────────────────────────────────

/**
 * Reports input the SanitizationProcessor cannot sanitize: its rules name
 * fields, so the input must be an array of them. The message names the
 * type received, never the value.
 *
 * Preconditions: built only through the named constructor.
 *
 * Thread-safety: an exception, created per failure.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     throw UnsupportedInputException::notAnArray(get_debug_type($input));
 *
 * References: ADR-004; kariricode/contract Processor.
 */
final class UnsupportedInputException extends ValidationBase implements SanitizerException
{
    public static function notAnArray(string $type): self
    {
        return new self(SanitizerCode::UnsupportedInput, "The sanitization processor expects an array of fields, {$type} given.");
    }
}
