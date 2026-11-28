<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Exception;

use KaririCode\Exception\ConfigurationException as ConfigurationBase;

// ── Exception: InvalidRuleException ──────────────────────────────────
// A rule is defined, registered or configured wrongly (codes 3301–3306).
// ─────────────────────────────────────────────────────────────────────

/**
 * Reports a mistake in how rules are written down: an alias registered
 * twice or never registered, a rule definition that is neither an alias,
 * a SanitizationRule nor [rule, parameters], a parameter a rule needs and
 * did not get or got with the wrong type, named arguments on
 * #[Sanitize]. Sanitization never fails because of the input, so every
 * exception here is the application's mistake.
 *
 * Preconditions: built only through the named constructors.
 *
 * Thread-safety: an exception, created per failure.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     throw InvalidRuleException::unknownAlias('trimm');
 *
 * References: ADR-004; SPEC-001 §5.
 */
final class InvalidRuleException extends ConfigurationBase implements SanitizerException
{
    public static function duplicateAlias(string $alias): self
    {
        return new self(SanitizerCode::DuplicateRuleAlias, 'A rule is already registered under the alias ' . self::quote($alias) . '.');
    }

    public static function unknownAlias(string $alias): self
    {
        return new self(SanitizerCode::UnknownRuleAlias, 'No rule is registered under the alias ' . self::quote($alias) . '.');
    }

    public static function invalidDefinition(string $field, string $reason): self
    {
        return new self(SanitizerCode::InvalidRuleDefinition, 'Invalid rule definition for field ' . self::quote($field) . ": {$reason}.");
    }

    public static function emptyAlias(): self
    {
        return new self(SanitizerCode::InvalidRuleDefinition, 'A rule alias must not be empty.');
    }

    public static function missingParameter(string $field, string $parameter): self
    {
        return new self(SanitizerCode::MissingRuleParameter, 'A rule on field ' . self::quote($field) . ' needs the parameter ' . self::quote($parameter) . '.');
    }

    public static function missingBounds(string $field): self
    {
        return new self(SanitizerCode::MissingRuleParameter, 'A rule on field ' . self::quote($field) . ' needs the parameter "min", "max" or both.');
    }

    public static function invalidParameter(string $field, string $parameter, string $expected, string $given = ''): self
    {
        $given = $given === '' ? '' : ", {$given} given";

        return new self(SanitizerCode::InvalidRuleParameter, 'The parameter ' . self::quote($parameter) . ' of a rule on field ' . self::quote($field) . " must be {$expected}{$given}.");
    }

    /**
     * @param list<string> $names
     */
    public static function namedArguments(string $attribute, array $names): self
    {
        return new self(SanitizerCode::NamedAttributeArguments, "#[{$attribute}] takes no named arguments (" . implode(', ', $names) . "); pass parameters as ['alias', ['parameter' => value]].");
    }
}
