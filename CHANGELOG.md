# Changelog

All notable changes to `kariricode/sanitizer` are documented here. The
format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and the project uses [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] — 2026-11-28

A new line, built from the September 2026 component tarball (33 rules,
ARFA 1.3; it labelled itself 3.1.0 and was never published) and brought
to KaririCode Specification V4.3.3. It replaces the 1.x line (last
1.2.3, March 2026, on kariricode/property-inspector 2). Read the
upgrade notes below before updating from either.

### Spec V4.3.3 conformance (2026-11-28)

**Breaking**

- PHP 8.4. Requires `kariricode/contract` ^2.12 and `kariricode/exception`
  ^2.0.2; `kariricode/property-inspector` ^3.0 is a suggestion for
  attribute sanitization, no longer a requirement (ADR-003).
- `SanitizationRule::getName()` is `name()` (ADR-001).
- `SanitizationContext`: the properties `$field`, `$data` and
  `$parameters` replace `getFieldName()`, `getRootData()` and
  `getParameters()`; `parameter()` replaces `getParameter()`; typed
  readers `intParameter()`, `numberParameter()`, `stringParameter()`,
  `boolParameter()`, `arrayParameter()`, and `hasField()`/`fieldValue()`
  by dotted path. `SanitizationContextImpl` is `Core\RuleContext`.
- `SanitizerEngine`: all constructor arguments are optional
  (`new SanitizerEngine()` uses the 33 built-in aliases); `registry` and
  `configuration` are public properties (`getConfiguration()` is gone);
  an `observer` closure receives events.
- `SanitizationResult` is immutable: `original`, `sanitized` and
  `modifications` are properties (`getOriginalData()`,
  `getSanitizedData()`, `getModifications()`, `addModification()`,
  `setSanitizedValue()` and `merge()` are gone) (ADR-002).
- `SanitizerConfiguration::$preserveOriginal` is removed; the original
  is always in the result.
- `AttributeSanitizer` takes a contract `PropertyInspector` and
  `PropertyChangeApplier` and returns a `SanitizedObject` (the object,
  or a sanitized copy, and the result) instead of modifying the object
  and returning the result (ADR-003).
- `ProcessorBridge` is replaced by `Integration\SanitizationProcessor`
  (contract `Processor` and `Sanitizer`); `SanitizerServiceProvider`
  implements the contract `ServiceProvider` instead of offering
  `createRegistry()`, `createEngine()` and `createAttributeSanitizer()`
  (ADR-006). `SanitizationCompletedEvent` has no `$timestamp`.
- Exceptions carry SAN codes 3300–3399 on the kariricode/exception
  families (ADR-004): `InvalidRuleException` (Configuration),
  `ConfigurationException` and `UnsupportedInputException` (new);
  `SanitizationException`, never thrown, is removed.
- Misconfiguration throws instead of passing silently: malformed rule
  definitions; `pad` without `length` or with an empty `pad` or an
  unknown `side`; `truncate` with a `max` shorter than its suffix;
  `replace` with an empty `search`; `regex_replace` without a pattern or
  with one that does not compile; `clamp` without bounds or with `min`
  above `max`; `round` with an unknown `mode`; an unknown charset for
  `html_encode` and `html_decode`; an unknown time zone; any parameter
  of the wrong type (ADR-001, ADR-004).
- `html_purify` refuses an `allowed` parameter: it keeps no tag
  (ADR-005).
- `to_int` converts only without loss: "12.9" and values beyond the
  integer range stay as they were (ADR-008).
- Fields the input lacks are no longer created (ADR-002).

**Added**

- Alphanumeric CNPJ in `format_cnpj` (IN RFB nº 2.229/2024) (ADR-008).
- `SanitizedObject`; sanitization of readonly DTOs into copies.
- `RuleContext::boolParameter()`; `SanitizationResult` value semantics.
- Events emitted to an optional observer (the 1.x line declared them
  and never emitted them).
- The quality gates through KaririCode Devkit, CI, SBOM, security
  checks (ADR-007); a conformance suite of 30 checks.

**Changed**

- Every definition of a rule map is resolved before the data is read,
  so a misspelled alias shows even when the input lacks the field.
- Rules see the data as given, whatever the order of fields in the map.
- `strip_non_printable` also removes C1 controls, zero-width spaces,
  the byte order mark and the bidirectional marks, embeddings,
  overrides and isolates (Trojan Source, CVE-2021-42574); it keeps the
  zero-width joiner.
- `pad`, `truncate` and the case rules count UTF-8 characters.
- `timestamp_to_date` keeps a fraction as microseconds and accepts the
  years 1 to 9999.
- `email_filter` no longer trims separately: FILTER_SANITIZE_EMAIL
  removes whitespace anywhere.
- `clamp` returns the bound as given (an int value clamped to 9.5
  returns 9.5, not 9).
- `alpha_only`, `alphanumeric_only`, `normalize_whitespace` and
  `strip_non_printable` replace invalid UTF-8 before matching.

**Fixed**

- `html_purify` decoded entities after removing tags, so
  `&lt;script&gt;alert(1)&lt;/script&gt;` came out as
  `<script>alert(1)</script>`. It now decodes first, removes `script`
  and `style` elements with their content, then every tag (ADR-005).
- `strip_tags` left the content of `script` and `style` elements in the
  text.
- Nested fields were read by dotted path but written back as a flat
  key (`$sanitized['user.email']`), leaving the nested value
  unsanitized.
- `normalize_date` accepted dates PHP rolls over (31/02/2026 became
  2026-03-03) and took the current time for fields its format did not
  name.
- `regex_replace` and `strip_non_printable` returned the unsanitized
  input when PCRE failed; they now return the empty string.
- `to_bool` turned INF into true; `to_int` truncated fractions.
- `format_cnpj` dropped the letters of an alphanumeric CNPJ.
- `#[Sanitize]` on readonly properties threw `Error: Cannot modify
  readonly property`.

## [1.2.3] — 2026-03-05

The March 2026 rewrite on PHP 8.4 and kariricode/property-inspector 2.
1.0.0 to 1.2.2 (October 2024) ran on PHP 8.3 with
kariricode/processor-pipeline 1 and kariricode/property-inspector 1.
See the git history.

## Upgrading from 1.x or the tarball

1. Rename `getName()` to `name()` in custom rules; read parameters with
   the typed readers and the context's properties.
2. Build the engine with `new SanitizerEngine()` (or pass your
   registry); bind the provider to a container instead of calling its
   factory methods.
3. Build an `AttributeSanitizer` with a `PropertyInspector` and a
   `PropertyChangeApplier`
   (`composer require kariricode/property-inspector`), and use
   `$sanitized->object`: the original object is no longer modified.
4. Read `$result->sanitized` instead of `getSanitizedData()`; drop
   `preserveOriginal`.
5. Give `pad` a `length`; check rule maps that relied on silent
   defaults; round before `to_int` where truncation was intended.
6. Catch `SanitizerException` or a family instead of SPL exceptions.
7. Expect missing fields to stay missing.
