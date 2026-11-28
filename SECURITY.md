# Security Policy — kariricode/sanitizer

Part of the KaririCode Framework. Security baseline: OWASP ASVS 5.0, target
level 2 (`extra.kariricode.asvs` in `composer.json`), KaririCode
Specification V4.3.3 §20–§24 and §32.

## Reporting a vulnerability

E-mail **walmir.silva@kariricode.org** with a description, the affected
version and a minimal reproduction. Do not open a public issue for
vulnerabilities. The fix timeline is agreed per report.

## Supported Versions

| Version | Status |
|---|---|
| 2.0.0 | Supported once tagged; QD-V433 PASS (`docs/quality/QD-V433-COMPLIANCE.md`) |
| 1.2.3 and earlier | Not supported; `html_purify` in 1.x turns entity-encoded markup into markup (see CHANGELOG, Fixed) |

## Threat Model

The package cleans data an application received before the application
uses it. The rules, the rule maps, the attributes and the configuration
are trusted; the data is not: its values, types, sizes and the presence
of each field come from whoever sent it. The threats are a sanitizer
that produces dangerous output from harmless-looking input, one that
silently changes what a value means, one that silently stops cleaning
because it was written down wrongly, input that makes a rule do
unbounded work, and an application that mistakes sanitized for valid
or safe in every context.

```
client ─► data ─► SanitizerEngine ─► rules (pure functions of value and parameters)
                      │                 └── misconfiguration ─► InvalidRuleException (the application's)
                      └── SanitizationResult: original, sanitized, modifications ─► validator ─► encoding at output
```

| Threat | Control in this package | Residual risk | ASVS 5.0 |
|---|---|---|---|
| Encoded markup turned into markup | `html_purify` decodes once, first, then removes `script`/`style` elements and every tag; nothing is decoded afterwards | Its output is text that may hold `<` and `&`: encode it for the output context | V1.1.1, V1.1.2 |
| Script content kept as text | `strip_tags` and `html_purify` remove `script` and `style` elements with their content | Tags `strip_tags` is told to allow keep their attributes (`onclick`, `javascript:`); rich text needs an HTML sanitizer library | V1.3.1 |
| Text that reads differently from what it holds | `strip_non_printable` removes C0/C1 controls, zero-width spaces, BOM and the bidirectional marks, embeddings, overrides and isolates (CVE-2021-42574) | Homoglyphs and confusable characters are not handled | V1.3.3 |
| Values whose meaning changes | Conversions only without loss (`to_int`, dates read strictly, `clamp` keeps its bounds); a value a rule cannot use passes untouched | A value left untouched is not validated: run the validator after the sanitizer | V2.2.1 |
| A rule that stops cleaning because of a typo | Missing or mistyped parameters, unknown aliases and charsets, patterns that do not compile and malformed definitions throw with a SAN code, whatever the input; every definition is checked before the data is read | A rule left out of the map is not detected | V2.2.1 |
| Unbounded work | Built-in patterns are linear; when PCRE hits its backtracking limit, `strip_tags`, `html_purify` and `regex_replace` return the empty string instead of the input | A pattern the application gives `regex_replace` can still be slow up to that limit; bound input size at the transport | V1.3.12 |
| Long input reaching storage or other systems | `truncate` (characters, not bytes), `digits_only`, `alpha_only`, `alphanumeric_only`, `email_filter` | Limits are the application's choice | V1.3.3 |
| Values leaking through logs | Exception messages name fields, aliases and types, never values, quoted through kariricode/exception (control characters neutralized); `SanitizationStartedEvent` carries field paths only | `SanitizationCompletedEvent` and `FieldModification` hold values by design: redact before logging | V16.2.5 |
| Shared state between requests | Rules, contexts, results and the engine are immutable; objects are sanitized into copies; no static state | A registry is mutable until shared: register at boot | V15.4 |

## ASVS 5.0

Level 2 is the target. The controls above map to these tests:

| Control | Test |
|---|---|
| Decode once, before stripping | `TextRulesTest` (`html_purify:` cases, the encoded script among them) |
| Script and style content removed | `TextRulesTest` (`strip_tags: everything`, `html_purify: tags and scripts`) |
| Invisible and direction characters removed | `TextRulesTest` (`strip_non_printable:` cases) |
| Lossless conversions, strict dates | `ValueRulesTest` (`to_int:`, `clamp:`, `normalize_date:`, `timestamp_to_date:` cases) |
| Misconfiguration throws | `misconfiguredRulesThrow` in both rule suites (with a value of another type too), `SanitizerEngineTest::malformedDefinitionsAreRefused`, `definitionsAreCheckedEvenForFieldsTheDataLacks`, `RuleContextTest` |
| Pathological input fails closed | `TextRulesTest` (`regex_replace: input PCRE cannot read`) |
| Messages without values | `ExceptionsTest` |
| Originals never modified | `AttributeSanitizerTest`, `ContactFormFlowTest::aDtoIsCleanedIntoACopyThatNeedsNoMoreCleaning` |
| No static state | the conformance rule on static properties and variables |
| No secrets committed | `tools/secret-scan.php` and its self-test |

## Supply Chain

- Runtime dependencies are `kariricode/contract` and
  `kariricode/exception`; `kariricode/property-inspector` is suggested.
  No network, file or process access.
- `composer.lock` is committed, resolved from Packagist with a clean
  Composer home, with no path repository.
- `composer security` validates the manifest and the lock, audits the
  runtime lock and the toolchain lock (`.kcode/composer.lock`) for
  advisories, and scans the tree for secrets.
- `composer sbom` writes a CycloneDX 1.5 document; CI keeps it as a build
  artifact.
- The toolchain is pinned in `devkit.php`; CI installs kcode with a
  verified checksum, pins every action by its full commit SHA and runs
  the dependency review on pull requests.
- Release tags are created by the maintainer (Spec V4.3.3 §32).

## Hardening checklist for applications

- Sanitize, then validate the sanitized value, then encode at output for
  the context (HTML, attribute, URL, SQL parameters); none replaces the
  others.
- Use `html_purify` for text fields that arrive with markup, and an HTML
  sanitizer library for rich text that must keep it.
- Bound the size of request bodies before sanitizing them.
- Register custom rules at boot, then share the engine.
- Redact `SanitizationCompletedEvent::$result` before writing it to logs.
- Treat `SanitizerException` from the Configuration family as a bug in
  the rule map, not as bad input.
