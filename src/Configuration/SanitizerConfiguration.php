<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Configuration;

// ── Configuration: SanitizerConfiguration ────────────────────────────
// Whether the engine records the changes the rules make.
// ─────────────────────────────────────────────────────────────────────

/**
 * Turns the record of changes on (the default) or off. Without it, the
 * result still holds the input and the sanitized copy; only the list of
 * FieldModification entries stays empty, which saves memory on large
 * inputs.
 *
 * Preconditions: none.
 *
 * Thread-safety: immutable.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     new SanitizerEngine(configuration: new SanitizerConfiguration(trackModifications: false));
 *
 * References: ADR-002; SPEC-001 §8.
 */
final readonly class SanitizerConfiguration
{
    public function __construct(
        public bool $trackModifications = true,
    ) {}
}
