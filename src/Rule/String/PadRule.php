<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Rule\String;

use KaririCode\Sanitizer\Contract\SanitizationContext;
use KaririCode\Sanitizer\Contract\SanitizationRule;
use KaririCode\Sanitizer\Exception\InvalidRuleException;

// ── Rule: PadRule ────────────────────────────────────────────────────
// Pads a string to a length, in characters.
// ─────────────────────────────────────────────────────────────────────

/**
 * Pads to 'length' characters (UTF-8) with 'pad' (a space unless set) on
 * the 'side' given: right (the default), left or both. Strings already
 * that long are left as they are.
 *
 * Preconditions: 'length' is an integer; 'pad' is a non-empty string; 'side' is left, right or both.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['code' => [['pad', ['length' => 6, 'pad' => '0', 'side' => 'left']]]]
 *
 * References: PHP mb_str_pad(); SPEC-002 §1.
 */
final readonly class PadRule implements SanitizationRule
{
    #[\Override]
    public function sanitize(mixed $value, SanitizationContext $context): mixed
    {
        $length = $context->intParameter('length');
        $pad = $context->stringParameter('pad', ' ');
        $side = match ($context->stringParameter('side', 'right')) {
            'right' => STR_PAD_RIGHT,
            'left' => STR_PAD_LEFT,
            'both' => STR_PAD_BOTH,
            default => throw InvalidRuleException::invalidParameter(field: $context->field, parameter: 'side', expected: 'left, right or both'),
        };

        if ($pad === '') {
            throw InvalidRuleException::invalidParameter(field: $context->field, parameter: 'pad', expected: 'a non-empty string');
        }

        return is_string($value) ? mb_str_pad(string: $value, length: $length, pad_string: $pad, pad_type: $side, encoding: 'UTF-8') : $value;
    }

    #[\Override]
    public function name(): string
    {
        return 'string.pad';
    }
}
