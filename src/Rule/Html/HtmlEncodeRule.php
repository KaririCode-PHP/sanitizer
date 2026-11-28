<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Rule\Html;

use KaririCode\Sanitizer\Contract\SanitizationContext;
use KaririCode\Sanitizer\Contract\SanitizationRule;
use KaririCode\Sanitizer\Core\HtmlCharset;

// ── Rule: HtmlEncodeRule ─────────────────────────────────────────────
// Encodes the HTML special characters of a string.
// ─────────────────────────────────────────────────────────────────────

/**
 * Escapes &, <, >, " and ' with htmlspecialchars(), the flags given
 * (ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5 unless set) and the
 * 'encoding' (UTF-8 unless set); 'double_encode' false leaves existing
 * entities alone. Encode at output time when you can: a value encoded
 * at input and again at output shows the entities.
 *
 * Preconditions: 'flags' is an integer; 'encoding' is a charset htmlspecialchars() supports; 'double_encode' is a boolean.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['comment' => ['html_encode']]
 *
 * References: PHP htmlspecialchars(); OWASP XSS Prevention Cheat Sheet, rule 1; ADR-005; SPEC-002 §2.
 */
final readonly class HtmlEncodeRule implements SanitizationRule
{
    #[\Override]
    public function sanitize(mixed $value, SanitizationContext $context): mixed
    {
        $flags = $context->intParameter('flags', ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5);
        $encoding = HtmlCharset::of($context);
        $doubleEncode = $context->boolParameter('double_encode', true);

        return is_string($value) ? htmlspecialchars(string: $value, flags: $flags, encoding: $encoding, double_encode: $doubleEncode) : $value;
    }

    #[\Override]
    public function name(): string
    {
        return 'html.encode';
    }
}
