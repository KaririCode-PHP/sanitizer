# KaririCode\Sanitizer — Documentation

## Architecture

```
array data + rule map ────────────────► SanitizerEngine::sanitize(data, fieldRules)
                                           │  SanitizationStartedEvent ─► observer
object + #[Sanitize] ─► AttributeSanitizer │  every definition resolved: RuleRegistry ─► SanitizationRule
   PropertyInspector (kariricode/contract) │  per field present: DataPath, RuleContext
   AttributeRules (the handler) ───────────┤  per rule:  value = sanitize(value) ─► FieldModification
   PropertyChangeApplier ─► a copy ◄───────┤  value written back where it was read
                                           │  SanitizationResult (immutable) ─► caller
                                           └  SanitizationCompletedEvent ─► observer

kariricode/processor-pipeline ─► SanitizationProcessor: the sanitized array
kariricode/contract container ─► SanitizerServiceProvider: registry, engine, AttributeSanitizer
```

## Index

| Document | Subject |
|---|---|
| [ADR-001](adr/ADR-001-rules-registry-and-definitions.md) | Stateless rules, a registry of aliases, definitions checked up front, typed parameters |
| [ADR-002](adr/ADR-002-chained-rules-paths-and-results.md) | Chained rules on dotted paths, immutable results, the record of changes |
| [ADR-003](adr/ADR-003-attributes-through-the-inspection-ports.md) | Attribute sanitization into copies through the kariricode/contract Inspection ports |
| [ADR-004](adr/ADR-004-error-codes-and-misconfiguration.md) | Error codes SAN 3300–3399; misconfiguration throws; unusable values pass untouched |
| [ADR-005](adr/ADR-005-html-rules-and-xss.md) | HTML rules: decode before stripping, remove script content, encode at output |
| [ADR-006](adr/ADR-006-observer-and-contract-integrations.md) | The observer, the contract Processor, Sanitizer and ServiceProvider |
| [ADR-007](adr/ADR-007-toolchain-through-kcode.md) | Quality gates through KaririCode Devkit |
| [ADR-008](adr/ADR-008-lossless-conversions-and-formats.md) | Lossless conversions, strict dates, Unicode-aware filters, the alphanumeric CNPJ |
| [SPEC-001](spec/SPEC-001-sanitization-engine.md) | The engine: rules, context, definitions, results, attributes, configuration |
| [SPEC-002](spec/SPEC-002-rule-reference.md) | The 33 rules |
| [QD-V433-COMPLIANCE](quality/QD-V433-COMPLIANCE.md) | Quality record against Spec V4.3.3 |
