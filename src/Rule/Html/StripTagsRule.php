<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Rule\Html;

use KaririCode\Sanitizer\Contract\SanitizationContext;
use KaririCode\Sanitizer\Contract\SanitizationRule;
use KaririCode\Sanitizer\Exception\InvalidRuleException;

// ── Rule: StripTagsRule ──────────────────────────────────────────────
// Removes HTML tags, and script and style elements with their content.
// ─────────────────────────────────────────────────────────────────────

/**
 * Removes script and style elements with what they contain, then every
 * tag (and comment) strip_tags() recognizes, except the 'allowed' ones.
 * Allowed tags keep their attributes, so onclick and javascript: URLs
 * survive with them: for untrusted HTML that must keep markup, use an
 * HTML sanitizer; for text, use html_purify. When PCRE gives up on
 * hostile input (the backtracking limit), the result is the empty
 * string, never the input.
 *
 * Preconditions: 'allowed', when given, is a string ('<b><i>') or a list of tag names.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['title' => ['strip_tags'], 'note' => [['strip_tags', ['allowed' => ['b', 'i']]]]]
 *
 * References: PHP strip_tags(); OWASP XSS Prevention Cheat Sheet; ADR-005; SPEC-002 §2.
 */
final readonly class StripTagsRule implements SanitizationRule
{
    #[\Override]
    public function sanitize(mixed $value, SanitizationContext $context): mixed
    {
        /** @var mixed $allowed */
        $allowed = $context->parameter('allowed');

        if ($allowed !== null && !is_string($allowed) && !(is_array($allowed) && array_is_list($allowed) && array_all($allowed, static fn(mixed $tag): bool => is_string($tag)))) {
            throw InvalidRuleException::invalidParameter(field: $context->field, parameter: 'allowed', expected: 'a string or a list of tag names', given: get_debug_type($allowed));
        }

        if (!is_string($value)) {
            return $value;
        }

        /** @var list<string>|string|null $allowed */
        return strip_tags(preg_replace(pattern: '#<(script|style)\b[^>]*>.*?</\1\s*>#is', replacement: '', subject: $value) ?? '', $allowed);
    }

    #[\Override]
    public function name(): string
    {
        return 'html.strip_tags';
    }
}
