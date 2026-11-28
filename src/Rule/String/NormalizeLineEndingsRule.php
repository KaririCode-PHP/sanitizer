<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Rule\String;

use KaririCode\Sanitizer\Contract\SanitizationContext;
use KaririCode\Sanitizer\Contract\SanitizationRule;

// ── Rule: NormalizeLineEndingsRule ───────────────────────────────────
// Converts line endings to LF.
// ─────────────────────────────────────────────────────────────────────

/**
 * Turns Windows (CRLF) and old Mac (CR) line endings into LF.
 *
 * Preconditions: none; other types are returned unchanged.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['message' => ['normalize_line_endings']]
 *
 * References: SPEC-002 §1.
 */
final readonly class NormalizeLineEndingsRule implements SanitizationRule
{
    #[\Override]
    public function sanitize(mixed $value, SanitizationContext $context): mixed
    {
        return is_string($value) ? str_replace(search: ["\r\n", "\r"], replace: "\n", subject: $value) : $value;
    }

    #[\Override]
    public function name(): string
    {
        return 'string.normalize_line_endings';
    }
}
