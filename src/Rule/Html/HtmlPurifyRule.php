<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Rule\Html;

use KaririCode\Sanitizer\Contract\SanitizationContext;
use KaririCode\Sanitizer\Contract\SanitizationRule;
use KaririCode\Sanitizer\Exception\InvalidRuleException;

// ── Rule: HtmlPurifyRule ─────────────────────────────────────────────
// Turns HTML into plain text.
// ─────────────────────────────────────────────────────────────────────

/**
 * Decodes entities first, so markup written as entities is recognized,
 * then removes script and style elements with their content, removes
 * every tag, and trims. The result is text that holds no tag a browser
 * would run, but it is text: encode it where you output it. Nothing is
 * decoded after the tags are removed, so "&lt;script&gt;" cannot come
 * back as markup. No tag is kept; use strip_tags to keep some. When PCRE
 * gives up on hostile input (the backtracking limit), the result is the
 * empty string, never the input.
 *
 * Preconditions: no parameters; 'allowed' is refused, because no tag is kept.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['bio' => ['html_purify']]; // '<p>Hi &lt;b&gt;you&lt;/b&gt;</p>' → 'Hi you'
 *
 * References: OWASP XSS Prevention Cheat Sheet; ADR-005; SPEC-002 §2.
 */
final readonly class HtmlPurifyRule implements SanitizationRule
{
    #[\Override]
    public function sanitize(mixed $value, SanitizationContext $context): mixed
    {
        if ($context->hasParameter('allowed')) {
            throw InvalidRuleException::invalidParameter(field: $context->field, parameter: 'allowed', expected: 'absent: html_purify keeps no tag; use strip_tags to keep some');
        }

        if (!is_string($value)) {
            return $value;
        }

        $decoded = html_entity_decode(string: $value, flags: ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, encoding: 'UTF-8');

        return trim(strip_tags(preg_replace(pattern: '#<(script|style)\b[^>]*>.*?</\1\s*>#is', replacement: '', subject: $decoded) ?? ''));
    }

    #[\Override]
    public function name(): string
    {
        return 'html.purify';
    }
}
