<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Attribute;

use Attribute;
use KaririCode\Sanitizer\Contract\SanitizationRule;
use KaririCode\Sanitizer\Exception\InvalidRuleException;

// ── Attribute: Sanitize ──────────────────────────────────────────────
// Declares the sanitization rules of a property.
// ─────────────────────────────────────────────────────────────────────

/**
 * Lists, on a property, the rule definitions AttributeSanitizer applies
 * to its value in order, in the same forms the engine accepts: an alias,
 * a rule instance, or [alias or rule, parameters]. Repeatable; the rules
 * of every #[Sanitize] on a property run in order.
 *
 * Preconditions: rules are positional; named arguments would be lost
 * between the attribute and the engine, so they throw
 * InvalidRuleException.
 *
 * Thread-safety: immutable.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     #[Sanitize('trim', 'lower_case')]
 *     public string $email;
 *
 *     #[Sanitize(['truncate', ['max' => 160]])]
 *     public string $bio;
 *
 * References: ADR-003; SPEC-001 §7.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
final readonly class Sanitize
{
    /** @var list<string|SanitizationRule|array<array-key, mixed>> */
    public array $rules;

    /**
     * @param string|SanitizationRule|array<array-key, mixed> ...$rules
     */
    public function __construct(string|SanitizationRule|array ...$rules)
    {
        $named = [];
        $list = [];

        foreach ($rules as $key => $rule) {
            if (is_string($key)) {
                $named[] = $key;
            } else {
                $list[] = $rule;
            }
        }

        if ($named !== []) {
            throw InvalidRuleException::namedArguments(attribute: 'Sanitize', names: $named);
        }

        $this->rules = $list;
    }
}
