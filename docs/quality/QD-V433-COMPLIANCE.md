# QD-V433-COMPLIANCE: kariricode/sanitizer 2.0.0

- **Status: PASS** (automated gates)
- **Date:** 2026-11-28
- **Component:** kariricode/sanitizer 2.0.0; kariricode/contract ^2.12 (2.12.1 locked), kariricode/exception ^2.0.2 (2.0.2 locked); kariricode/property-inspector ^3.0 suggested
- **Standards:** KaririCode Spec V4.3.3 §29 (gates) and §32 (supply chain); ARFA 1.3 level 1 (Structural); OWASP ASVS 5.0 level 2
- **Authors:** Walmir Silva <walmir.silva@kariricode.org>

Every automated gate of §29.1 passes, locally (§1) and in CI: run
[46157329758](https://github.com/KaririCode-PHP/sanitizer/actions/runs/46157329758)
passed the gates on PHP 8.4.28 and the suites on PHP 8.5.13 with the
lowest dependencies (kariricode/contract 2.12.0, kariricode/exception
2.0.2), at `3c066b0`. The status turns FAIL if a gate fails. PASS covers
the automated gates (G1, G4, G5, G6); G3 needs a second reviewer (§8). No score is assigned (E-006). This is the first quality
record of the 2.x line: measured with this release's configurations,
the September 2026 tarball had 38 PHPStan errors at level max and 108
Psalm errors at level 1, with no mutation testing.

## 1. Gates (Spec V4.3.3 §29.1)

Measured locally on 2026-11-28 (§2). CI reported the same tests (351,
3,921 assertions) and the same mutants (521: 519 killed, 2 escaped).

| Gate | Command | Budget | Measured | Result |
|---|---|---|---|---|
| Tests | `composer test` | all pass, no warning, notice, deprecation or risky test | 351 tests: unit 318, integration 3 (a company contact form with a custom rule, a pipeline that sanitizes before it checks, a DTO cleaned into a copy), conformance 30 | pass |
| Conformance | `composer test-conformance` | all pass | 30 tests: `ArfaConformanceTest` 27 (25 rules and 2 documentation checks, §3), `CodeBlockRegistryTest` 3 | pass |
| PHPStan | `composer phpstan` | level max on `src` and `tests`, strict and PHPUnit rules, cognitive complexity ≤ 15 | 0 errors | pass |
| Psalm | `composer psalm` | level 1 on `src`, no baseline | 0 errors; `#[\Override]` required; `NoInterfaceProperties` suppressed in `psalm.xml` (tool limitation, ADR-007) | pass |
| Coding standard | `composer cs-check` | PER-CS (E-045, E-046) | 0 of 78 files to fix | pass |
| Complexity | `composer complexity`, `composer phpstan` | cyclomatic ≤ 10, nesting ≤ 3, parameters ≤ 4 per method of `src`; cognitive ≤ 15 (§13) | 158 methods; no accepted breach (empty baseline); two soft warnings: `RuleContext` has 12 public methods and `SanitizationContext` 11 (soft budget 10), the typed parameter readers | pass |
| Mutation | `composer mutation` | MSI ≥ 80 and covered-code MSI ≥ 80 (§19.4) | 521 mutants: 519 killed, 2 escaped; MSI 99.62 %, covered-code MSI 99.62 %; mutation code coverage 100 % (§7) | pass |
| Security | `composer security` | valid manifest and lock, no advisory, no secret | manifest and lock valid; no advisories in the runtime lock or in `.kcode/composer.lock`; secret scan 0 findings (self-test ok) | pass |

Line coverage of `src` by the unit and integration suites is 100 % (381
of 381 statements; PCOV, `phpunit --coverage-clover`); §29.1 sets no
coverage budget, mutation testing is the gate (§7).

`composer sbom` writes a CycloneDX 1.5 document with 2 runtime components
(kariricode/contract 2.12.1, kariricode/exception 2.0.2).

The README examples were run outside the repository: the quick start
and the rule map as printed; attribute sanitization against
kariricode/property-inspector 3.0.0 (`ReflectionPropertyInspector`,
`ReflectionChangeApplier`, a readonly DTO); the pipeline against
kariricode/processor-pipeline 2.0.0 and kariricode/validator 3.0.0.

## 2. Environment and Metadata

| Item | Value |
|---|---|
| Runtime | PHP 8.4.28 CLI, PCOV |
| Toolchain | KaririCode Devkit 1.2.2 with the root configs (ADR-007): PHPUnit 11.5.56, PHPStan 2.3.7 with phpstan-strict-rules 2.1.2 and phpstan-phpunit 2.1.4, Psalm 6.19.1, PHP-CS-Fixer 3.95.27, Infection 0.35.5, tomasvotruba/cognitive-complexity 1.2.0 |
| Lock | `composer.lock` resolved from Packagist with a clean Composer home; no path repository |
| `extra.kariricode` (gate G4, errata E-044) | `arfa` 1.3 level 1, `asvs` 5.0 level 2, `spec` 4.3.3, `error-code-range` SAN 3300–3399 |
| Error codes | `SanitizerCode`, 8 codes in 3301–3340, prefix SAN; the block is claimed on kariricode/exception's main, not yet in a release (§8) |

## 3. Conformance Rules (§29.2)

`tests/Conformance/ArfaConformanceTest.php`; all 27 pass.

| Rule | Reference |
|---|---|
| Every source file is strict and declares exactly one named type | §25, PSR-4 |
| Docblocks meet the five comment criteria (C1 summary; Preconditions, Thread-safety, Side effects, Example, References) | §17 |
| Section markers are 72 columns wide (sources and tests) | §25 |
| Concrete classes are final | §5 |
| Values (8 listed: the attribute, the settings, the rule context, events and results) are final readonly | §5.2 |
| Readonly classes declare no property hooks, nor `readonly` again inside | E-040 |
| Class constants are typed | §25 |
| Inheritance inside the package is at most two levels deep | §18 |
| No type keeps mutable static state | §5 |
| Names and methods avoid the forbidden patterns (Interface suffix, Manager/Helper/Util, toArray/fromArray/jsonSerialize, magic accessors) | §18, E-001 |
| Public methods are not bare getters (no parameter) or setters (one parameter) | §18 |
| Regular expressions anchor the end with `\z`, never `$` | §24 |
| Call sites with three or more arguments name them (the printf family and calls of a callable the application supplies are exempt) | §30 |
| Contracts are interfaces (3) | §5, §31 |
| Enums are pure unless an external contract reads the value (`SanitizerCode`), with the justification written down | E-003 |
| Every directory is a namespace segment | PSR-4 |
| Error codes are unique and inside the reserved block | §8 |
| Exceptions are built only by their named constructors; nothing throws an uncoded exception | §8 |
| Every concrete exception belongs to exactly one contract family, implements the package marker and extends a kariricode/exception base | §8.4 |
| `composer.json` declares the ARFA, ASVS and Spec versions and the code range; the runtime is PHP, kariricode/contract and kariricode/exception; property-inspector is suggested; no `require-dev` | §29.3 G4, E-044, §31 |
| kariricode/contract and kariricode/exception namespaces are imported only where they belong (Inspection by the attribute classes and the provider, DI by the provider, Processor and Sanitizer by the integration, the exception bases by `Exception\`) | §31 |
| The 33 rules are final readonly and implement `SanitizationRule` | §5.2, ADR-001 |
| `object` parameters only where the kariricode/contract Inspection ports have them, and for the copy they return | §30, ADR-003 |
| `composer.json` exposes the eight quality gates | §29.1 |
| Supply chain is pinned: lock committed, CI actions by commit SHA | §32 |

Two further tests check the documentation: 8 ADRs with the seven
canonical sections, this record's status line, the SECURITY.md sections
and the CHANGELOG headings; and that the README, SECURITY.md,
CONTRIBUTING.md, the ADRs, the SPECs, this record and the sources cite
Spec V4.3.3.

`CodeBlockRegistryTest` checks that `SanitizerCode` is a valid code
table, that the installed CODE-BLOCKS.md leaves 3300–3399 and the prefix
SAN to this package (and, where it lists the block, that the row
matches), and the documented sub-ranges.

## 4. Error Model (§8)

Every exception implements the marker `SanitizerException`, which
declares `$errorCode` (ADR-004): `InvalidRuleException` (Configuration;
3301–3306), `ConfigurationException` (Configuration; 3320) and
`UnsupportedInputException` (Validation; 3340). `getCode()` is the
`SanitizerCode` number; messages start with `[SAN_<code>]` and quote
identifiers. Rules never throw because of the input; `sanitize()`
throws only for mistakes in rules, rule maps and bindings, and the
processor for input that is not an array.

## 5. Security Controls (ASVS 5.0, level 2 declared)

| Control | Test |
|---|---|
| Entities decoded once, before tags are removed (V1.1.1) | `TextRulesTest` (`html_purify:` cases) |
| Script and style content removed (V1.3.1, residual risk in SECURITY.md) | `TextRulesTest` (`strip_tags:`, `html_purify:` cases) |
| Controls, zero-width and bidirectional characters removed (V1.3.3) | `TextRulesTest` (`strip_non_printable:` cases) |
| Conversions without loss; strict dates (V2.2.1) | `ValueRulesTest` |
| Misconfiguration throws instead of disabling a rule (V2.2.1) | the `misconfiguredRulesThrow` tests, `SanitizerEngineTest::malformedDefinitionsAreRefused`, `definitionsAreCheckedEvenForFieldsTheDataLacks`, `RuleContextTest` |
| Input PCRE cannot process fails closed (V1.3.12) | `TextRulesTest` (`regex_replace: input PCRE cannot read`) |
| Messages without values (V16.5.1) | `ExceptionsTest` |
| Originals never modified; no static state (V15.4) | `AttributeSanitizerTest`, the conformance rule on static state |
| No secrets committed | `tools/secret-scan.php` and its self-test |

SECURITY.md keeps the threat model, the residual risks and the
supply-chain controls.

## 6. Judgment Calls

| Topic | Decision | Reason |
|---|---|---|
| Base | the September 2026 tarball as 2.0.0 | the tarball is the reviewed design with events and the observer; 1.2.3 is published, so the new line is a major; the tarball's own label, 3.1.0, was never published |
| `html_purify` | decode, remove script and style elements, strip, trim; `allowed` refused | the earlier order produced markup from entities (ADR-005) |
| Values a rule cannot use | returned unchanged, never coerced with loss | sanitizing is not validating (ADR-004, ADR-008) |
| Misconfiguration | thrown, with SAN codes, whatever the input | a rule that silently does nothing is a security defect (ADR-004) |
| Absent fields | not created | a validator downstream must still see "missing" (ADR-002) |
| Attributes | into copies through the kariricode/contract Inspection ports, property-inspector suggested | readonly DTOs, property-inspector ADR-006 and ADR-009 (ADR-003) |
| CNPJ letters | all 26, upper case | IN RFB nº 2.229/2024, as kariricode/validator ADR-008 |
| `strip_non_printable` | removes U+200E and U+200F too, keeps U+200D | Trojan Source; emoji sequences (ADR-008) |
| Code block | 3300–3399 under SAN, the lowest free block after VAL | registry rule 3 |
| Psalm | `NoInterfaceProperties` suppressed | Psalm 6.19 does not model PHP 8.4 interface properties; PHPStan and PHP check them (ADR-007) |
| PER-CS 3.1 | checked with `@PER-CS3.0` | PHP-CS-Fixer 3.95 implements 3.0 (E-046) |

## 7. Mutation Testing

Infection 0.35.5 with `infection.json5`: every default mutator on `src`,
the unit and integration suites, only the test cases that cover each
mutant, a 5 s time-out.

| Run | Since the previous run | Mutants | Escaped | MSI | Covered-code MSI |
|---|---|---|---|---|---|
| 1 | first measurement, after the rework | 526 | 23 | 95.63 % | 95.63 % |
| 2 | tests for multiparameter definitions, repeated fields in `modifiedFields()`, accented capitals, multibyte strings within `max`, double encoding by default, lower-case charsets in `html_decode`, quotes in `html_purify`, integers on float bounds; `(string)` casts of `preg_replace()` replaced by `?? ''`; integrality read with `fmod()`; `email_filter` without the redundant `trim()` and `mb_strtolower()`; `timestamp_to_date` through `createFromTimestamp()`; the charset check shared by `html_encode` and `html_decode` | 521 | 2 | 99.62 % | 99.62 % |

Run 2 is this record's. The escaped mutants are equivalent:

| File | Escaped | Why equivalent |
|---|---|---|
| `Core/SanitizerEngine.php` | 2 | the divisor that turns nanoseconds into milliseconds, 1,000,000, changed by one: the duration moves by one part per million |

## 8. Open Items

| # | Item | Closes when |
|---|---|---|
| 1 | G3: independent review for P0 findings | a second reviewer signs off |
| 2 | kariricode/exception's main claims SAN 3300–3399; the installed 2.0.2 does not list it, so `CodeBlockRegistryTest` checks that the block is free | a kariricode/exception release ships the row |
| 3 | Release v2.0.0; Packagist is auto-updated and serves 1.2.3 until then | the maintainer tags the release |
| 4 | Escaped mutants (§7) | each is killed by a test or stays documented as equivalent |
