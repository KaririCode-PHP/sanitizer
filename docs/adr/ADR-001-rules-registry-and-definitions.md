# ADR-001: Stateless Rules, a Registry of Aliases and Rule Definitions

## Status

Accepted — V2.0.0

## Date

2026-11-28

## Context

Both lines this release starts from — the March 2026 main line
(published as 1.2.3) and the September 2026 component tarball, which
labelled itself 3.1.0 and was never published — shipped the same 33
rules behind the same shape: a `SanitizationRule` with `sanitize()` and
`getName()`, an `InMemoryRuleRegistry`, and an engine that resolved a
field's rule definitions while it sanitized. Three problems came with
that shape:

- The engine required a registry and the 33 aliases lived in
  `SanitizerServiceProvider::registerBuiltinRules()`, so
  `new SanitizerEngine(new InMemoryRuleRegistry())` knew no rule at all.
- A definition was never checked: `['truncate' => 5]`, `[42]` or
  `['trim', 5]` ended in a `TypeError`, an undefined index or a rule
  that ignored its parameters.
- Rules read parameters with silent fallbacks: `pad` without `length`
  padded to 0, a `truncate` whose `max` was a string truncated at 255,
  `regex_replace` without `pattern` returned its input. A typo in a
  rule map changed nothing and reported nothing.

### Prior Art

- kariricode/validator ADR-001 (3.0.0): the same registry, definitions
  and typed parameter readers, for validation rules.
- Laravel's and Symfony's validators: string aliases with parameters,
  checked when the rule set is built.

## Decision

1. `SanitizationRule` has `sanitize(mixed $value, SanitizationContext
   $context): mixed` and `name(): string`. Rules are final readonly and
   hold no state, so one instance serves every field and request; the
   conformance suite checks all 33.
2. `InMemoryRuleRegistry::withDefaults()` registers the 33 aliases, and
   `new SanitizerEngine()` uses it. Applications register their own
   rules under new aliases; a duplicate or empty alias throws.
3. A field's rules are a list of definitions, each one of: an alias, a
   rule instance, `[alias or rule]`, or `[alias or rule, parameters]`
   with the parameters a map of names to values. Anything else throws
   `InvalidRuleException` (SAN_3303) naming the field and what a
   definition may be.
4. The engine resolves every definition of the map before it looks at
   the data, so a misspelled alias throws even when this input lacks
   the field.
5. Rules read parameters through the context's typed readers
   (`intParameter()`, `numberParameter()`, `stringParameter()`,
   `boolParameter()`, `arrayParameter()`). A parameter that is present
   with the wrong type throws (SAN_3305) even when the rule has a
   default, which covers absence only; a required one that is absent
   throws SAN_3304. Rules read their parameters before they look at the
   value, so a misconfiguration shows whatever the input.

## Consequences

### Positive

- A wrong rule map fails on the first call, in a test, with a message
  that names the field and the parameter.
- `new SanitizerEngine()` works out of the box; the provider no longer
  owns the rule list.
- Rules are shared and reusable, and a custom rule is one small class.

### Negative

- Definitions are resolved on every call; there is no compiled plan to
  cache. The cost grows with the number of rules, not with the data.
- A typo in the rules of a field that is rarely present now breaks
  every call, which is the intent but can surprise an upgrade.

## Alternatives Considered

- **Resolve definitions only for fields present in the input** (the
  earlier behavior): cheaper on sparse input, and a typo then waits for
  the production record that has the field.
- **Compile a rule map into a cached plan object:** worth it when a
  profile shows resolution matters; nothing does yet.
- **Keep lenient parameters with defaults for wrong types:** the
  earlier behavior, the one that turned typos into silent no-ops.

## References

- kariricode/validator ADR-001.
- SPEC-001 §3–§5; SPEC-002.
- ADR-004 (codes and exceptions).
