# SPEC-001: KaririCode\Sanitizer 2.0 — The Engine

- **Version:** 2.0.0
- **Standards:** KaririCode Specification V4.3.3; ARFA 1.3 level 1 (Structural); OWASP ASVS 5.0 level 2
- **Error code block:** SAN 3300–3399 (ADR-004)

## 1. Purpose

Clean data before an application uses it: arrays by a rule map, objects
by `#[Sanitize]` attributes. Each field passes through its rules in
order; every change is recorded; values a rule cannot use pass
untouched, and rules written down wrongly are refused. Sanitizing
prepares data, validating checks it: run the validator after the
sanitizer. The rules are specified in SPEC-002.

## 2. Architecture

```
src/
├── Attribute/      Sanitize
├── Configuration/  SanitizerConfiguration
├── Contract/       SanitizationRule, SanitizationContext, RuleRegistry
├── Core/           SanitizerEngine, RuleContext, InMemoryRuleRegistry, AttributeSanitizer, AttributeRules;
│                   DataPath, NumericValue, DateValue, HtmlCharset (shared by rules)
├── Event/          SanitizationStartedEvent, SanitizationCompletedEvent
├── Exception/      SanitizerCode, the marker SanitizerException and three final exceptions
├── Integration/    SanitizationProcessor (kariricode/contract Processor and Sanitizer)
├── Provider/       SanitizerServiceProvider (kariricode/contract ServiceProvider)
├── Result/         SanitizationResult, FieldModification, SanitizedObject
└── Rule/           33 rules in Brazilian, Date, Filter, Html, Numeric, String, Type
```

```
sanitize(data, fieldRules)
  ├── SanitizationStartedEvent ─► observer
  ├── for each field: definitions → [rule, parameters] (InvalidRuleException when malformed)
  │     ├── the data lacks the field → skip (nothing is created)
  │     ├── value = DataPath::value(data, field)
  │     └── for each rule: value = sanitize(value, context(field, parameters))
  │           └── value changed and tracking on → FieldModification
  │     └── sanitized = DataPath::with(sanitized, field, value)
  ├── SanitizationResult (immutable: original, sanitized, modifications)
  └── SanitizationCompletedEvent ─► observer
```

## 3. Rules

`SanitizationRule::sanitize(mixed $value, SanitizationContext $context): mixed`
returns the cleaned value; `name(): string` names the rule in the
record of changes (`string.trim`, `html.purify`, …). Invariants:

| ID | Invariant |
|---|---|
| R-1 | A rule holds no state between calls; built-in rules are `final readonly`. |
| R-2 | A rule returns a value of a type it does not handle, or one it cannot convert without loss, unchanged; it never throws because of the input. |
| R-3 | A rule reads its parameters before the value, through the typed readers, so a misconfiguration throws whatever the value. |
| R-4 | When PHP gives up on an input (PCRE's backtracking limit), a rule returns the empty string, never the input. |

## 4. The Context and Field Paths

`RuleContext` implements `SanitizationContext`: `field`, `data` (the
whole input, as given), `parameters`; `withField()` and
`withParameters()` return copies (parameters merge, later wins).

| Reader | Accepts | Absent without default | Present with another type |
|---|---|---|---|
| `parameter($name, $default)` | anything | `$default` | — |
| `intParameter()` | int | SAN_3304 | SAN_3305 |
| `numberParameter()` | int, finite float | SAN_3304 | SAN_3305 |
| `stringParameter()` | string | SAN_3304 | SAN_3305 |
| `boolParameter()` | bool | SAN_3304 | SAN_3305 |
| `arrayParameter()` | array | SAN_3304 | SAN_3305 |

A default covers absence only: a parameter given as null is a parameter
of the wrong type.

Field paths (`Core\DataPath`): a literal key first, else segments split
at dots, integer keys by their digits. `hasField()` tells a missing
field from one set to null; `fieldValue()` reads null for both.
`DataPath::with()` returns a copy with the value replaced at the path;
it creates the intermediate arrays a missing path needs, and the engine
calls it only for fields the data has.

## 5. Rule Maps, Definitions and Exceptions

`sanitize(array $data, array $fieldRules): SanitizationResult`. Each key
of `$fieldRules` is a field path (integer keys become strings); each
value is a list of definitions:

| Definition | Meaning |
|---|---|
| `'alias'` | the registry's rule, no parameters |
| `SanitizationRule` | that rule, no parameters |
| `[alias or rule]` | the same |
| `[alias or rule, parameters]` | with parameters, a map of names to values |

Every definition of the map is resolved before the data is read.

| Code | Class | Family | Named constructors | Raised when |
|---|---|---|---|---|
| 3301 | InvalidRuleException | Configuration | duplicateAlias | an alias is registered twice |
| 3302 | InvalidRuleException | Configuration | unknownAlias | a definition names no registered rule |
| 3303 | InvalidRuleException | Configuration | invalidDefinition, emptyAlias | a malformed definition or an empty alias |
| 3304 | InvalidRuleException | Configuration | missingParameter, missingBounds | a required parameter is absent |
| 3305 | InvalidRuleException | Configuration | invalidParameter | a parameter has the wrong type or value |
| 3306 | InvalidRuleException | Configuration | namedArguments | `#[Sanitize]` received named arguments |
| 3320 | ConfigurationException | Configuration | invalidBinding | a container binding has the wrong type |
| 3340 | UnsupportedInputException | Validation | notAnArray | the processor received something other than an array |

Messages carry `[SAN_NNNN]`, quote identifiers through
kariricode/exception and name types, never values.

## 6. Results and Modifications

| Type | Content |
|---|---|
| `FieldModification` | `field`, `ruleName`, `before`, `after` |
| `SanitizationResult` | `original`, `sanitized`, `modifications`; `get()`, `wasModified()`, `isFieldModified()`, `modifiedFields()`, `modificationsFor()`, `modificationCount()` |

Invariants:

| ID | Invariant |
|---|---|
| INV-001 | The rules of a field run in the order listed, each on the previous output. |
| INV-002 | A rule whose output is not identical (`!==`) to its input adds one modification, when tracking is on. |
| INV-003 | Modifications keep the order of fields and rules. |
| INV-004 | A field the data lacks is neither sanitized nor created. |
| INV-005 | Field paths are dotted; a literal key wins; the value is written where it was read. |
| INV-006 | Rules see the data as given, whatever the order of fields in the map. |
| INV-007 | `original` is the input; results are immutable. |
| INV-008 | `isFieldModified()` and `wasModified()` compare values, so a change of type counts. |

## 7. Attributes

`#[Sanitize(...$definitions)]` (repeatable, positional only, SAN_3306
for named arguments). `AttributeSanitizer::sanitize(object $object):
SanitizedObject` inspects `Sanitize` through a kariricode/contract
`PropertyInspector` into `AttributeRules` (repeated attributes join
their rules in order), sanitizes the collected values, and hands the
properties whose value changed to a kariricode/contract
`PropertyChangeApplier`, which returns a copy. With no change, the
original instance is returned and the applier is not called.
`SanitizedObject` holds `object` and `result`.

## 8. Configuration

| Setting | Default | Effect |
|---|---|---|
| `trackModifications` | true | false records no `FieldModification`; values are sanitized the same |

## 9. Events and Integrations

- Observer: `SanitizationStartedEvent(fields, timestamp)` with the field
  paths of the map, `SanitizationCompletedEvent(result, durationMs)`
  measured on the monotonic clock. The completed event carries values.
- `SanitizationProcessor(engine, fieldRules)`: `process()` and
  `sanitize()` return the sanitized array; non-arrays throw SAN_3340.
- `SanitizerServiceProvider(configuration, observer)`: binds
  `RuleRegistry`, `SanitizerEngine` and `AttributeSanitizer` as
  singletons; the last resolves the container's `PropertyInspector` and
  `PropertyChangeApplier`. A binding of the wrong type throws SAN_3320.

## 10. ARFA 1.3 and Spec V4.3.3

| Rule | How |
|---|---|
| P1 immutable state | rules, contexts, results, events, settings and the engine are `final readonly`; the registry is mutable until shared; objects are sanitized into copies |
| P2 flow | definitions → rules → modifications → result |
| P4 protocol agnosticism | arrays and objects in, a result out; no HTTP, no I/O |
| P5 observability | coded exceptions; start and completion events |
| E-003 pure enums | SanitizerCode is the only enum, backed for `getCode()` |
| §13, §17, §18, §29, §30, §32 | checked by the conformance suite and the gates (ADR-007) |

## 11. File Inventory

| Directory | Files |
|---|---|
| src/Attribute | 1 |
| src/Configuration | 1 |
| src/Contract | 3 |
| src/Core | 9 |
| src/Event | 2 |
| src/Exception | 5 |
| src/Integration | 1 |
| src/Provider | 1 |
| src/Result | 3 |
| src/Rule | 33 |
| tests/Unit | 8 |
| tests/Integration | 1 |
| tests/Conformance | 2 |
| tests/Support | 5 |
