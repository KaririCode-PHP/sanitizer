<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Exception;

use KaririCode\Exception\ErrorCode;
use Throwable;

// ── Exception: SanitizerException ────────────────────────────────────
// Marks every exception kariricode/sanitizer throws.
// ─────────────────────────────────────────────────────────────────────

/**
 * Lets a caller catch whatever this package throws with one type, while
 * each concrete exception also belongs to one kariricode/contract family
 * (Configuration or Validation) through its kariricode/exception base.
 *
 * Preconditions: none.
 *
 * Thread-safety: implementations are exceptions, created per failure.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     try {
 *         $engine->sanitize($data, $rules);
 *     } catch (SanitizerException $e) {
 *         $code = $e->errorCode; // a SanitizerCode case
 *     }
 *
 * References: ADR-004.
 */
interface SanitizerException extends Throwable
{
    public ErrorCode $errorCode { get; }
}
