<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Rule\String;

use KaririCode\Sanitizer\Contract\SanitizationContext;
use KaririCode\Sanitizer\Contract\SanitizationRule;

// ── Rule: StripNonPrintableRule ──────────────────────────────────────
// Removes control and invisible formatting characters.
// ─────────────────────────────────────────────────────────────────────

/**
 * Removes the C0 controls except tab, line feed and carriage return,
 * DEL, the C1 controls, the zero-width space, the byte order mark and
 * the bidirectional marks, embeddings, overrides and isolates (U+200E,
 * U+200F, U+202A–U+202E, U+2066–U+2069) that can make text read
 * differently from what it contains. Invalid UTF-8 bytes become "?"
 * first. Zero-width joiners stay: emoji and several scripts need them.
 *
 * Preconditions: none; other types are returned unchanged.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['comment' => ['strip_non_printable']]
 *
 * References: Unicode TR #9 (bidirectional controls); CVE-2021-42574 (Trojan Source); ADR-008; SPEC-002 §1.
 */
final readonly class StripNonPrintableRule implements SanitizationRule
{
    #[\Override]
    public function sanitize(mixed $value, SanitizationContext $context): mixed
    {
        return is_string($value) ? preg_replace(pattern: '/[\x{00}-\x{08}\x{0B}\x{0C}\x{0E}-\x{1F}\x{7F}-\x{9F}\x{200B}\x{200E}\x{200F}\x{202A}-\x{202E}\x{2066}-\x{2069}\x{FEFF}]/u', replacement: '', subject: mb_scrub($value, 'UTF-8')) ?? '' : $value;
    }

    #[\Override]
    public function name(): string
    {
        return 'string.strip_non_printable';
    }
}
