<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Rule\String;

use KaririCode\Sanitizer\Contract\SanitizationContext;
use KaririCode\Sanitizer\Contract\SanitizationRule;
use KaririCode\Sanitizer\Exception\InvalidRuleException;

// ── Rule: TruncateRule ───────────────────────────────────────────────
// Cuts a string to a maximum number of characters.
// ─────────────────────────────────────────────────────────────────────

/**
 * Leaves strings of at most 'max' characters (255 unless set) as they
 * are; cuts longer ones so that, with the 'suffix' ("..." unless set)
 * appended, they have exactly 'max' characters. Counts characters
 * (UTF-8), not bytes.
 *
 * Preconditions: 'max' is 0 or more and at least the length of the suffix.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['bio' => [['truncate', ['max' => 160, 'suffix' => '…']]]]
 *
 * References: SPEC-002 §1.
 */
final readonly class TruncateRule implements SanitizationRule
{
    #[\Override]
    public function sanitize(mixed $value, SanitizationContext $context): mixed
    {
        $max = $context->intParameter('max', 255);
        $suffix = $context->stringParameter('suffix', '...');
        $room = $max - mb_strlen($suffix, 'UTF-8');

        if ($room < 0) {
            throw InvalidRuleException::invalidParameter(field: $context->field, parameter: 'max', expected: 'at least the length of the suffix', given: (string) $max);
        }

        if (!is_string($value) || mb_strlen($value, 'UTF-8') <= $max) {
            return $value;
        }

        return mb_substr(string: $value, start: 0, length: $room, encoding: 'UTF-8') . $suffix;
    }

    #[\Override]
    public function name(): string
    {
        return 'string.truncate';
    }
}
