<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Contract;

// ── Contract: SanitizationRule ───────────────────────────────────────
// One transformation that makes a value safer or cleaner.
// ─────────────────────────────────────────────────────────────────────

/**
 * Returns the value made clean (trimmed, encoded, converted, formatted)
 * and names itself for the record of changes. A rule returns values it
 * does not handle unchanged instead of throwing, so rules chain safely;
 * it never fails because of the input. Rules hold no state between
 * calls: whatever varies per use comes in the context's parameters.
 *
 * Preconditions: sanitize() receives any value, including null.
 *
 * Thread-safety: implementations must be stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     final readonly class CollapseDashesRule implements SanitizationRule
 *     {
 *         public function sanitize(mixed $value, SanitizationContext $context): mixed
 *         {
 *             return is_string($value) ? preg_replace(pattern: '/-{2,}/', replacement: '-', subject: $value) ?? '' : $value;
 *         }
 *
 *         public function name(): string
 *         {
 *             return 'custom.collapse_dashes';
 *         }
 *     }
 *
 * References: ADR-001; SPEC-001 §3.
 */
interface SanitizationRule
{
    public function sanitize(mixed $value, SanitizationContext $context): mixed;

    public function name(): string;
}
