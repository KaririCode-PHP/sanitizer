<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Event;

// ── Event: SanitizationStartedEvent ──────────────────────────────────
// Announces a sanitization before its first rule runs.
// ─────────────────────────────────────────────────────────────────────

/**
 * Tells the observer which fields are about to be sanitized and when
 * (Unix time in seconds, with microseconds). It carries field names only,
 * never values.
 *
 * Preconditions: none.
 *
 * Thread-safety: immutable.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     $engine = new SanitizerEngine(observer: static fn (object $event) => $dispatcher->dispatch($event));
 *
 * References: ADR-006; ARFA 1.3 P5.
 */
final readonly class SanitizationStartedEvent
{
    /**
     * @param list<string> $fields
     */
    public function __construct(
        public array $fields,
        public float $timestamp,
    ) {}
}
