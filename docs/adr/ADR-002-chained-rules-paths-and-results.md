# ADR-002: Chained Rules on Dotted Paths, Immutable Results and a Record of Changes

## Status

Accepted — V2.0.0

## Date

2026-11-28

## Context

A sanitizer is a pipeline per field: trim, then lower-case, then
filter. Both earlier lines chained rules that way and recorded each
change as a `FieldModification`, which is worth keeping: an audit of
what the sanitizer altered is the only way to tell a user why the value
they typed is not the value that was stored. Around that core, four
things were wrong:

- `SanitizationResult` was mutable: `addModification()`,
  `setSanitizedValue()` and `merge()` were public, so any holder could
  rewrite a result after the fact.
- Fields were read by dotted path (`'user.email'` walked into
  `['user' => ['email' => …]]`) but written back as a flat key
  (`$sanitized['user.email']`), leaving the nested value unsanitized
  next to a new top-level key.
- A field the input lacked was added: as null, or as whatever the rules
  made of null (`to_array` turned it into `[]`), so sanitizing changed
  the shape of the data and a validator downstream saw a value instead
  of a missing field.
- `SanitizerConfiguration::$preserveOriginal` was documented and read
  by nothing; the original was always kept.

### Prior Art

- kariricode/validator ADR-002: an immutable result, dotted paths,
  literal keys first.
- Laminas InputFilter and Symfony's form data transformers: one value
  per field passed through an ordered list of filters.

## Decision

1. The rules of a field run in the order listed; each receives the
   previous rule's output. When a rule returns a value that is not
   identical (`!==`) to what it received, the engine records
   `FieldModification(field, ruleName, before, after)`, unless
   `SanitizerConfiguration::$trackModifications` is false.
2. A field name is a dotted path into nested arrays; a key that
   contains dots itself is matched first, so flat form data with dotted
   names works too. The sanitized value is written back where it was
   read, in a copy (`Core\DataPath::with()`); integer keys are paths
   like any other.
3. A field the data lacks is skipped and not created. A field present
   with null runs its rules, which leave null alone unless they say
   otherwise (`to_array` makes it `[]`).
4. Rules see the data as it was given (`$context->data`), not the copy
   being sanitized, so the order of fields in a rule map never changes
   a result.
5. `SanitizationResult` is a final readonly value holding `original`,
   `sanitized` and `modifications`, with `get()`, `wasModified()`,
   `isFieldModified()` (by value, so `'42'` becoming `42` counts),
   `modifiedFields()`, `modificationsFor()` and `modificationCount()`.
   `preserveOriginal` is removed.

## Consequences

### Positive

- Results can be passed around and logged without defensive copies.
- Nested input stays nested; the absent stays absent, which keeps a
  later "required" check meaningful.
- Sanitizing a sanitized value changes nothing for every rule except
  `html_encode` with `double_encode` (the default) and `url_encode`,
  which escape what they already escaped by nature; the integration
  suite checks a whole form for it.

### Negative

- Each changed field copies the arrays along its path.
- With tracking on, a modification holds the value before and after
  each rule; for bulk imports of large strings, turn tracking off.

## Alternatives Considered

- **Write back through references:** no copies, and it would mutate
  arrays the caller still holds.
- **Keep the flat write:** compatible with 1.2.3 and wrong for every
  nested field.
- **Create absent fields as null:** a stable output shape, at the cost
  of hiding "missing" from the validator that runs next.

## References

- kariricode/validator ADR-002.
- SPEC-001 §4, §6 and §8.
