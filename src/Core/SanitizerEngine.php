<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Core;

use Closure;
use KaririCode\Sanitizer\Configuration\SanitizerConfiguration;
use KaririCode\Sanitizer\Contract\RuleRegistry;
use KaririCode\Sanitizer\Contract\SanitizationRule;
use KaririCode\Sanitizer\Event\SanitizationCompletedEvent;
use KaririCode\Sanitizer\Event\SanitizationStartedEvent;
use KaririCode\Sanitizer\Exception\InvalidRuleException;
use KaririCode\Sanitizer\Result\FieldModification;
use KaririCode\Sanitizer\Result\SanitizationResult;

// ── Core: SanitizerEngine ────────────────────────────────────────────
// Runs the rules of each field in order and keeps a record of what changed.
// ─────────────────────────────────────────────────────────────────────

/**
 * Sanitizes an array of fields against a rule map: for each field present
 * in the input, it reads the value by its dotted path, passes it through
 * the field's rules in order (each rule receives the previous one's
 * output) and writes the result back at the same path of a copy. Fields
 * the input does not have are left out, so sanitizing adds no keys. With
 * tracking on, every change a rule made is recorded. The observer
 * receives a SanitizationStartedEvent and a SanitizationCompletedEvent
 * per call.
 *
 * Preconditions: a rule definition is an alias, a SanitizationRule, or a
 * list [alias or rule, parameters]; anything else is an
 * InvalidRuleException, as is an unknown alias or a missing parameter.
 *
 * Thread-safety: immutable; the registry is read, never written.
 *
 * Side effects: the observer is called twice per sanitization.
 *
 * Example:
 *
 *     $result = (new SanitizerEngine())->sanitize(
 *         ['email' => ' Ana@Example.com ', 'bio' => '<b>Hi</b>'],
 *         ['email' => ['trim', 'lower_case'], 'bio' => ['html_purify']],
 *     );
 *     $result->sanitized; // ['email' => 'ana@example.com', 'bio' => 'Hi']
 *
 * References: ADR-001; ADR-002; ADR-006; SPEC-001.
 */
final readonly class SanitizerEngine
{
    public RuleRegistry $registry;

    public SanitizerConfiguration $configuration;

    private ?Closure $observer;

    public function __construct(
        ?RuleRegistry $registry = null,
        ?SanitizerConfiguration $configuration = null,
        ?Closure $observer = null,
    ) {
        $this->registry = $registry ?? InMemoryRuleRegistry::withDefaults();
        $this->configuration = $configuration ?? new SanitizerConfiguration();
        $this->observer = $observer;
    }

    /**
     * @param array<array-key, mixed> $data       the fields to sanitize
     * @param array<array-key, mixed> $fieldRules field path => list of rule definitions
     */
    public function sanitize(array $data, array $fieldRules): SanitizationResult
    {
        $startedAt = hrtime(true);
        $this->emit(new SanitizationStartedEvent(fields: array_map(strval(...), array_keys($fieldRules)), timestamp: microtime(true)));
        $context = new RuleContext(data: $data);
        $sanitized = $data;
        $modifications = [];

        /** @var mixed $rules */
        foreach ($fieldRules as $field => $rules) {
            $field = (string) $field;
            $definitions = $this->definitions($field, $rules);

            if (!DataPath::exists($data, $field)) {
                continue;
            }

            $cleaned = $this->sanitizeField($context->withField($field), $definitions);
            $sanitized = DataPath::with(data: $sanitized, path: $field, value: $cleaned[0]);
            array_push($modifications, ...$cleaned[1]);
        }

        $result = new SanitizationResult(original: $data, sanitized: $sanitized, modifications: $modifications);
        $this->emit(new SanitizationCompletedEvent(result: $result, durationMs: (hrtime(true) - $startedAt) / 1_000_000));

        return $result;
    }

    /**
     * @param list<array{SanitizationRule, array<string, mixed>}> $definitions
     *
     * @return array{mixed, list<FieldModification>}
     */
    private function sanitizeField(RuleContext $context, array $definitions): array
    {
        /** @var mixed $value */
        $value = $context->fieldValue($context->field);
        $changes = [];

        foreach ($definitions as [$rule, $parameters]) {
            /** @var mixed $before */
            $before = $value;
            /** @var mixed $value */
            $value = $rule->sanitize($value, $context->withParameters($parameters));

            if ($this->configuration->trackModifications && $before !== $value) {
                $changes[] = new FieldModification(field: $context->field, ruleName: $rule->name(), before: $before, after: $value);
            }
        }

        return [$value, $changes];
    }

    /**
     * @return list<array{SanitizationRule, array<string, mixed>}>
     */
    private function definitions(string $field, mixed $rules): array
    {
        if (!is_array($rules) || !array_is_list($rules)) {
            throw InvalidRuleException::invalidDefinition(field: $field, reason: 'the rules of a field are a list');
        }

        return array_map(fn(mixed $definition): array => $this->definition($field, $definition), $rules);
    }

    /**
     * @return array{SanitizationRule, array<string, mixed>}
     */
    private function definition(string $field, mixed $definition): array
    {
        if ($definition instanceof SanitizationRule) {
            return [$definition, []];
        }

        if (is_string($definition)) {
            return [$this->registry->resolve($definition), []];
        }

        if (!is_array($definition) || !array_is_list($definition) || count($definition) > 2) {
            throw InvalidRuleException::invalidDefinition(field: $field, reason: 'a rule is an alias, a SanitizationRule, or [alias or rule, parameters]');
        }

        return [$this->rule($field, $definition[0] ?? null), $this->parameters($field, $definition[1] ?? [])];
    }

    private function rule(string $field, mixed $rule): SanitizationRule
    {
        if (is_string($rule)) {
            return $this->registry->resolve($rule);
        }

        return $rule instanceof SanitizationRule ? $rule : throw InvalidRuleException::invalidDefinition(field: $field, reason: 'the first item names the rule, by alias or as a SanitizationRule');
    }

    /**
     * @return array<string, mixed>
     */
    private function parameters(string $field, mixed $parameters): array
    {
        if (!is_array($parameters) || array_filter(array_keys($parameters), is_int(...)) !== []) {
            throw InvalidRuleException::invalidDefinition(field: $field, reason: 'the parameters of a rule are a map of names to values');
        }

        /** @var array<string, mixed> $parameters */
        return $parameters;
    }

    private function emit(SanitizationStartedEvent|SanitizationCompletedEvent $event): void
    {
        if ($this->observer !== null) {
            ($this->observer)($event);
        }
    }
}
