<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Rule\String;

use KaririCode\Sanitizer\Contract\SanitizationContext;
use KaririCode\Sanitizer\Contract\SanitizationRule;
use KaririCode\Sanitizer\Exception\InvalidRuleException;

// ── Rule: ReplaceRule ────────────────────────────────────────────────
// Replaces every occurrence of a substring.
// ─────────────────────────────────────────────────────────────────────

/**
 * Replaces each occurrence of 'search' with 'replace' (the empty string
 * unless set), case-sensitively.
 *
 * Preconditions: 'search' is a non-empty string; 'replace', when given, is a string.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['phone' => [['replace', ['search' => ' ', 'replace' => '']]]]
 *
 * References: PHP str_replace(); SPEC-002 §1.
 */
final readonly class ReplaceRule implements SanitizationRule
{
    #[\Override]
    public function sanitize(mixed $value, SanitizationContext $context): mixed
    {
        $search = $context->stringParameter('search');
        $replace = $context->stringParameter('replace', '');

        if ($search === '') {
            throw InvalidRuleException::invalidParameter(field: $context->field, parameter: 'search', expected: 'a non-empty string');
        }

        return is_string($value) ? str_replace(search: $search, replace: $replace, subject: $value) : $value;
    }

    #[\Override]
    public function name(): string
    {
        return 'string.replace';
    }
}
