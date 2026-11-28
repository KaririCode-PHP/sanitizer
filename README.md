# KaririCode Sanitizer

[![CI](https://github.com/KaririCode-PHP/sanitizer/actions/workflows/ci.yml/badge.svg)](https://github.com/KaririCode-PHP/sanitizer/actions/workflows/ci.yml)
![PHP 8.4+](https://img.shields.io/badge/PHP-8.4%2B-777BB4)
![ARFA 1.3 level 1](https://img.shields.io/badge/ARFA-1.3%20level%201-blue)
![License MIT](https://img.shields.io/badge/license-MIT-green)

Rule-based data sanitization for PHP 8.4: 33 built-in rules — strings,
HTML, numbers, types, dates, filters and Brazilian document formats,
the alphanumeric CNPJ included — applied to arrays by a rule map or to
readonly DTOs by `#[Sanitize]` attributes. Rules run in order, every
change is recorded, nested fields stay nested, and a value a rule cannot
use passes untouched for the validator to judge; a rule written down
wrongly is the application's mistake and throws a coded exception.
Part of the KaririCode Framework; Spec V4.3.3, ARFA 1.3 level 1.

## 1. Features

- **33 rules** in seven groups: string, HTML, numeric, type, date,
  filter, Brazilian (SPEC-002).
- **Chained rules, recorded changes** — each rule receives the previous
  one's output; every change is a `FieldModification` (field, rule,
  before, after) in an immutable result (ADR-002).
- **Rule maps** — an alias (`'trim'`), a rule instance, or
  `[alias or rule, parameters]`; fields by dotted path
  (`'address.city'`), written back where they were read; absent fields
  are not created.
- **Attributes** — `#[Sanitize]` on a DTO, read through the
  kariricode/contract `PropertyInspector` and written into a **copy**
  through the `PropertyChangeApplier`, so readonly DTOs work and the
  original is never modified; kariricode/property-inspector 3
  implements both (ADR-003).
- **Safe by construction** — `html_purify` decodes before it strips, so
  encoded markup cannot come back as markup; `strip_tags` removes script
  and style content; conversions never lose information; Unicode
  direction overrides are removed (ADR-005, ADR-008).
- **Strict parameters** — a missing length, a misspelled charset, a
  pattern that does not compile throw `InvalidRuleException` with a SAN
  code, whatever the input (ADR-004).
- **Integrations** — a kariricode/contract `Processor` and `Sanitizer`
  for pipelines, a `ServiceProvider` for containers, an observer that
  receives start and completion events (ADR-006).
- **No dependencies** beyond kariricode/contract and
  kariricode/exception.

## 2. Installation

```bash
composer require kariricode/sanitizer
composer require kariricode/property-inspector   # only for #[Sanitize] on objects
```

PHP 8.4 or later.

## 3. Quick Start

```php
<?php

declare(strict_types=1);

use KaririCode\Sanitizer\Core\SanitizerEngine;

require __DIR__ . '/vendor/autoload.php';

$engine = new SanitizerEngine();

$result = $engine->sanitize(
    [
        'email' => '  Ana.Silva@Example.COM ',
        'name' => "  ana   maria\tsilva ",
        'bio' => '<p>Dev &lt;script&gt;alert(1)&lt;/script&gt;no Cariri</p>',
        'cnpj' => '12abc34501de35',
        'age' => '42',
        'address' => ['cep' => '63100000'],
    ],
    [
        'email' => ['email_filter'],
        'name' => ['normalize_whitespace', 'capitalize'],
        'bio' => ['html_purify', ['truncate', ['max' => 140]]],
        'cnpj' => ['format_cnpj'],
        'age' => ['to_int'],
        'address.cep' => ['format_cep'],
        'phone' => ['digits_only'],
    ],
);

echo json_encode($result->sanitized, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), PHP_EOL;
```

```json
{
    "email": "ana.silva@example.com",
    "name": "Ana Maria Silva",
    "bio": "Dev no Cariri",
    "cnpj": "12.ABC.345/01DE-35",
    "age": 42,
    "address": {
        "cep": "63100-000"
    }
}
```

The encoded script in `bio` is decoded and removed with its content;
`phone` is not in the input, so it is not created.
`$result->modifiedFields()` lists the six fields that changed, and
`$result->modificationsFor('name')` the two steps of `name`.

## 4. Rule Maps

A rule map lists, per field, the rule definitions to apply in order:

| Definition | Example |
|---|---|
| alias | `'trim'` |
| rule instance | `new HtmlPurifyRule()` |
| alias with parameters | `['truncate', ['max' => 140, 'suffix' => '…']]` |
| rule with parameters | `[new ClampRule(), ['min' => 1, 'max' => 99]]` |

Field names are dotted paths into nested arrays; a key that contains
dots itself is matched first. Every definition is checked before the
data is read, so a misspelled alias throws even for a field this input
lacks.

Register your own rules under an alias:

```php
use KaririCode\Sanitizer\Contract\SanitizationContext;
use KaririCode\Sanitizer\Contract\SanitizationRule;

final readonly class CollapseDashesRule implements SanitizationRule
{
    public function sanitize(mixed $value, SanitizationContext $context): mixed
    {
        return is_string($value) ? preg_replace('/-{2,}/', '-', $value) ?? '' : $value;
    }

    public function name(): string
    {
        return 'app.collapse_dashes';
    }
}

$engine->registry->register('collapse_dashes', new CollapseDashesRule());
```

## 5. The Rules

| Group | Aliases |
|---|---|
| String | trim, lower_case, upper_case, capitalize, slug, truncate, normalize_whitespace, normalize_line_endings, pad, replace, regex_replace, strip_non_printable |
| Html | strip_tags, html_encode, html_decode, html_purify, url_encode |
| Numeric | to_int, to_float, clamp, round |
| Type | to_bool, to_string, to_array |
| Date | normalize_date, timestamp_to_date |
| Filter | digits_only, alpha_only, alphanumeric_only, email_filter |
| Brazilian | format_cpf, format_cnpj, format_cep |

[SPEC-002](docs/spec/SPEC-002-rule-reference.md) lists each rule's
parameters and output. Points worth knowing:

- `html_purify` makes text out of HTML; its output still needs
  `htmlspecialchars()` (or `html_encode`) where you write it into a
  page. Rich text that must keep markup needs an HTML sanitizer library
  (ADR-005).
- `to_int` converts "42", 42.0 and "1e3" and leaves "12.9" alone; round
  first if you mean to round (ADR-008).
- `normalize_date` reads strictly: 31/02/2026 and "28/11/2026 " come back
  unchanged instead of becoming another day.
- `format_cnpj` keeps letters (IN RFB nº 2.229/2024); the formatters do
  not check digits — validate with kariricode/validator.
- `email_filter` keeps ASCII only, as FILTER_SANITIZE_EMAIL does.

## 6. Attributes

```php
use KaririCode\PropertyInspector\Core\ReflectionChangeApplier;
use KaririCode\PropertyInspector\Core\ReflectionPropertyInspector;
use KaririCode\Sanitizer\Attribute\Sanitize;
use KaririCode\Sanitizer\Core\AttributeSanitizer;

final readonly class SignUp
{
    public function __construct(
        #[Sanitize('trim', 'lower_case')]
        public string $email,
        #[Sanitize('normalize_whitespace', 'capitalize')]
        public string $name,
        #[Sanitize('html_purify', ['truncate', ['max' => 160]])]
        public string $bio = '',
        #[Sanitize('format_cnpj')]
        public ?string $cnpj = null,
    ) {}
}

$sanitizer = new AttributeSanitizer($engine, new ReflectionPropertyInspector(), new ReflectionChangeApplier());

$sanitized = $sanitizer->sanitize($signUp);
$clean = $sanitized->object;      // a new SignUp; $signUp is unchanged
$sanitized->result;               // the SanitizationResult, with the record of changes
```

`#[Sanitize]` takes positional definitions only; named arguments throw.
Only properties whose value changed are written into the copy; when
nothing changed, `$sanitized->object` is the original instance. The
copy is built without running the constructor, so validate the copy.

## 7. Results

`SanitizationResult` holds `original`, `sanitized` and `modifications`:
`get('address.cep')`, `wasModified()`, `isFieldModified()`,
`modifiedFields()`, `modificationsFor()`, `modificationCount()`.

```php
$engine = new SanitizerEngine(configuration: new SanitizerConfiguration(trackModifications: false));
```

Without tracking, values are sanitized the same and no
`FieldModification` is kept — useful for bulk imports of large values.

## 8. Exceptions

Input never makes a rule throw. Exceptions are for mistakes in the
rules and for the processor's input type; every exception implements
`SanitizerException` and carries a code from SAN 3300–3399:

| Code | Exception | Family | Meaning |
|---|---|---|---|
| 3301–3306 | InvalidRuleException | Configuration | duplicate or unknown alias, malformed definition, missing or invalid parameter, named attribute arguments |
| 3320 | ConfigurationException | Configuration | a container binding of the wrong type |
| 3340 | UnsupportedInputException | Validation | the processor received something other than an array |

## 9. Integrations

```php
use KaririCode\ProcessorPipeline\Pipeline\Pipeline;
use KaririCode\Validator\Integration\ValidationProcessor;

// kariricode/processor-pipeline 2, or anything that takes a contract Processor:
// clean first, then validate the clean value
$pipeline = new Pipeline([
    new SanitizationProcessor($engine, ['email' => ['trim', 'lower_case']]),
    new ValidationProcessor($validator, ['email' => ['required', 'email']]),
]);
$clean = $pipeline->process(['email' => '  Ana@Example.COM ']);   // ['email' => 'ana@example.com']

// a kariricode/contract container
(new SanitizerServiceProvider())->register($container);

// events, for logs and metrics
$engine = new SanitizerEngine(observer: static fn (object $event) => $dispatcher->dispatch($event));
```

The processor returns the sanitized array; in a processor-pipeline, a
stage that throws reaches the caller as `PipelineExecutionException`
with the stage's exception as `getPrevious()`. The provider binds the
registry, the engine and the `AttributeSanitizer` (bind a
`PropertyInspector` and a `PropertyChangeApplier` for it). The observer
receives a `SanitizationStartedEvent` (field paths) and a
`SanitizationCompletedEvent` (result, duration in ms); the result holds
the input, so redact it before logging.

## 10. Security

Sanitizing is not validating: clean first, then validate the clean
value, and encode at output for the context you write into. See
[SECURITY.md](SECURITY.md) for the threat model and the ASVS 5.0
mapping.

## 11. Architecture and Quality

| Gate | Result |
|---|---|
| Tests | unit, integration and conformance suites; 100 % of lines covered |
| Static analysis | PHPStan level max (strict and PHPUnit rules), Psalm error level 1 |
| Mutation testing | MSI recorded in [QD-V433](docs/quality/QD-V433-COMPLIANCE.md) |
| Complexity | cyclomatic ≤ 10, nesting ≤ 3, parameters ≤ 4, cognitive ≤ 15 |

The gates run through KaririCode Devkit (`kcode`); see
[CONTRIBUTING.md](CONTRIBUTING.md). Design decisions are in
[docs/adr](docs/adr), the specification in [docs/spec](docs/spec).

## 12. License

MIT — see [LICENSE](LICENSE).
