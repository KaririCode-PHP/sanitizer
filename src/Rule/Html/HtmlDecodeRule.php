<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Rule\Html;

use KaririCode\Sanitizer\Contract\SanitizationContext;
use KaririCode\Sanitizer\Contract\SanitizationRule;
use KaririCode\Sanitizer\Core\HtmlCharset;

// ── Rule: HtmlDecodeRule ─────────────────────────────────────────────
// Decodes HTML entities in a string.
// ─────────────────────────────────────────────────────────────────────

/**
 * Turns entities back into characters with html_entity_decode(), the
 * flags given (ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5 unless set) and
 * the 'encoding' (UTF-8 unless set). Decoding can create markup out of
 * text ("&lt;script&gt;" becomes "<script>"): never decode a value that
 * is then output without encoding.
 *
 * Preconditions: 'flags' is an integer; 'encoding' is a charset html_entity_decode() supports.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['title' => ['html_decode', 'strip_tags']]
 *
 * References: PHP html_entity_decode(); ADR-005; SPEC-002 §2.
 */
final readonly class HtmlDecodeRule implements SanitizationRule
{
    #[\Override]
    public function sanitize(mixed $value, SanitizationContext $context): mixed
    {
        $flags = $context->intParameter('flags', ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5);
        $encoding = HtmlCharset::of($context);

        return is_string($value) ? html_entity_decode(string: $value, flags: $flags, encoding: $encoding) : $value;
    }

    #[\Override]
    public function name(): string
    {
        return 'html.decode';
    }
}
