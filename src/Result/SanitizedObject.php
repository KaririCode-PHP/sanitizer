<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Result;

// ── Result: SanitizedObject ──────────────────────────────────────────
// A sanitized copy of an object, with the result that produced it.
// ─────────────────────────────────────────────────────────────────────

/**
 * Returns what AttributeSanitizer produced: the object with its
 * #[Sanitize] properties cleaned (a copy when anything changed, the same
 * instance otherwise) and the SanitizationResult of its property values.
 *
 * Preconditions: built by AttributeSanitizer.
 *
 * Thread-safety: immutable; the object is whatever the change applier
 * returned.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     $sanitized = $sanitizer->sanitize($signUp);
 *     $signUp = $sanitized->object;
 *     $sanitized->result->modifiedFields(); // ['email', 'name']
 *
 * References: ADR-003.
 */
final readonly class SanitizedObject
{
    public function __construct(
        public object $object,
        public SanitizationResult $result,
    ) {}
}
