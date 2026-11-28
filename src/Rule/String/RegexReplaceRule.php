<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Rule\String;

use KaririCode\Sanitizer\Contract\SanitizationContext;
use KaririCode\Sanitizer\Contract\SanitizationRule;
use KaririCode\Sanitizer\Exception\InvalidRuleException;

// ── Rule: RegexReplaceRule ───────────────────────────────────────────
// Replaces the matches of a regular expression.
// ─────────────────────────────────────────────────────────────────────

/**
 * Replaces the matches of 'pattern' with 'replacement' (the empty string
 * unless set; $1 and \1 refer to groups). A pattern that does not
 * compile is the application's mistake and throws, whatever the value
 * is, so the mistake shows on the first call. When PCRE cannot
 * process the input (invalid UTF-8 under /u, the backtracking limit)
 * the result is the empty string: the input is not returned unsanitized.
 *
 * Preconditions: 'pattern' is a complete PCRE pattern with delimiters.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['handle' => [['regex_replace', ['pattern' => '/[^a-z0-9_]/i', 'replacement' => '']]]]
 *
 * References: PHP PCRE; ADR-004; SPEC-002 §1.
 */
final readonly class RegexReplaceRule implements SanitizationRule
{
    #[\Override]
    public function sanitize(mixed $value, SanitizationContext $context): mixed
    {
        $pattern = $context->stringParameter('pattern');
        $replacement = $context->stringParameter('replacement', '');

        if ($pattern === '' || @preg_match($pattern, '') === false) {
            throw InvalidRuleException::invalidParameter(field: $context->field, parameter: 'pattern', expected: 'a regular expression that compiles');
        }

        if (!is_string($value)) {
            return $value;
        }

        return preg_replace(pattern: $pattern, replacement: $replacement, subject: $value) ?? '';
    }

    #[\Override]
    public function name(): string
    {
        return 'string.regex_replace';
    }
}
