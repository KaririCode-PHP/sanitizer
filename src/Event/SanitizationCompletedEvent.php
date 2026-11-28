<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Event;

use KaririCode\Sanitizer\Result\SanitizationResult;

// ── Event: SanitizationCompletedEvent ────────────────────────────────
// Reports a finished sanitization and how long it took.
// ─────────────────────────────────────────────────────────────────────

/**
 * Gives the observer the result of a sanitization and its duration in
 * milliseconds, measured with the monotonic clock. The result holds the
 * input and its sanitized copy: log names and counts, not the result
 * itself, where the input may not go.
 *
 * Preconditions: none.
 *
 * Thread-safety: immutable.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     $observer = static function (object $event) use ($metrics): void {
 *         if ($event instanceof SanitizationCompletedEvent) {
 *             $metrics->observe('sanitization_ms', $event->durationMs);
 *         }
 *     };
 *
 * References: ADR-006; ARFA 1.3 P5.
 */
final readonly class SanitizationCompletedEvent
{
    public function __construct(
        public SanitizationResult $result,
        public float $durationMs,
    ) {}
}
