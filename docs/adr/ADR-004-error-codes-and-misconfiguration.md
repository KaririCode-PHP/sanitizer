# ADR-004: Error Codes SAN 3300–3399 on the Exception Families; Misconfiguration Throws, Unusable Values Pass Untouched

## Status

Accepted — V2.0.0

## Date

2026-11-28

## Context

Spec V4.3.3 §8.4 identifies a failure by its kariricode/contract
exception family plus a numeric code that points to one component. Both
earlier lines threw `\InvalidArgumentException` (unknown or duplicate
alias) and declared a `SanitizationException extends \RuntimeException`
that nothing threw, all without codes. The registry of record,
kariricode/exception's CODE-BLOCKS.md, had no block for the sanitizer.

A sanitizer also has to say what happens to a value a rule cannot use.
The earlier rules mixed three answers: return it unchanged (most),
coerce it anyway (`to_int` truncated "12.9" to 12, `to_bool` turned INF
into true), or return the unsanitized input when PHP failed
(`regex_replace` and `strip_non_printable` returned the input when PCRE
gave up).

### Prior Art

- kariricode/exception 2.0 (ADR-002 there): the registry, the family
  bases, `[PREFIX_NNNN]` messages with neutralized control characters.
- kariricode/validator ADR-004: VAL 3200–3299 and the same line between
  misconfiguration and input.

## Decision

1. **The block.** SAN 3300–3399, the lowest free block of 100 codes
   after the validator's (registry rule 3), laid out as 3300–3319 rule
   definitions and the registry, 3320–3329 container bindings,
   3340–3349 integrations. `SanitizerCode` is the code table (backed,
   ARFA 1.3 E-003); the registry row is added with this release.
2. **Exceptions.** Every exception implements `SanitizerException`
   (`$errorCode`), extends a kariricode/exception base, belongs to one
   family and is built only by named constructors:

   | Codes | Exception | Family |
   |---|---|---|
   | 3301–3306 | InvalidRuleException | Configuration |
   | 3320 | ConfigurationException | Configuration |
   | 3340 | UnsupportedInputException | Validation |

3. **Misconfiguration throws.** Mistakes in rules, rule maps,
   attributes and container bindings are the application's and throw
   (Configuration family), whatever the value being sanitized — rules
   read their parameters first.
4. **Values a rule cannot use pass untouched.** A rule returns a value
   of a type it does not handle, or a value it cannot convert without
   loss, exactly as it received it: sanitizing is not validating, and a
   validator downstream can still reject it (ADR-008). Sanitizing never
   throws because of the input, except in the processor, which accepts
   arrays only (SAN_3340).
5. **Failure is closed.** When PHP gives up on an input (PCRE's
   backtracking limit in `strip_tags`, `html_purify` and
   `regex_replace`), the rule returns the empty string, never the input
   it could not clean.
6. **Messages** name aliases, fields and parameters quoted with
   kariricode/exception's `quote()`, types instead of values, and never
   the data being sanitized.

## Consequences

### Positive

- A log line `[SAN_3304] A rule on field "code" needs the parameter
  "length".` names the component, the mistake and where to fix it.
- Catching `SanitizerException`, a family, or one class all work.
- Input never makes the sanitizer throw mid-request.

### Negative

- **Breaking:** code catching `\InvalidArgumentException` from the
  registry must change; `SanitizationException` is gone.
- A value left untouched can still be dirty: pair the sanitizer with a
  validator for anything that must have a shape.
- The registry row must reach a kariricode/exception release before the
  registry test can read it from an installed package; until then the
  test accepts a free block (QD-V433 open items).

## Alternatives Considered

- **SPL exceptions with codes:** no family, no prefix, no registry.
- **Throw for unusable input:** a sanitizer that rejects input is a
  validator with worse messages.
- **Coerce whatever can be coerced:** the earlier `to_int`, which
  turned a typed "12.9" into 12 without telling anyone.

## References

- Spec V4.3.3 §8.4; ARFA 1.3 E-003.
- kariricode/exception CODE-BLOCKS.md (rules 1–5), ADR-002;
  kariricode/validator ADR-004.
- SPEC-001 §5.
