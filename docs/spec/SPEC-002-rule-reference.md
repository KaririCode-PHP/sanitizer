# SPEC-002: KaririCode\Sanitizer 2.0 — Rule Reference

- **Version:** 2.0.0
- **Rules:** 33, all registered by alias in `InMemoryRuleRegistry::withDefaults()`

Every rule returns values of types it does not handle unchanged, and
values it cannot convert without loss unchanged (R-2); reads its
parameters before the value, so a missing parameter throws SAN_3304 and
one of the wrong type or value SAN_3305 whatever the input (R-3); and
returns the empty string when PCRE gives up on an input (R-4). "Name"
is what `name()` returns and what a `FieldModification` records.
Text rules count UTF-8 characters, not bytes.

## 1. String

| Alias | Name | Parameters | Result for strings |
|---|---|---|---|
| trim | string.trim | `characters` (string, default `" \t\n\r\0\x0B"`; `a..z` ranges) | trim() of both ends |
| lower_case | string.lower_case | — | mb_strtolower, UTF-8 |
| upper_case | string.upper_case | — | mb_strtoupper, UTF-8 |
| capitalize | string.capitalize | — | MB_CASE_TITLE: first letter of each word upper case, the rest lower case |
| slug | string.slug | `separator` (string, default `-`, inserted literally) | lower case, Latin letters with diacritics transliterated (á → a, ß → ss, æ → ae, ł → l), runs of `[a-z0-9]` joined by the separator; other scripts dropped |
| truncate | string.truncate | `max` (int, default 255, ≥ length of the suffix), `suffix` (string, default `...`) | unchanged up to `max` characters; else cut so that, with the suffix, it has exactly `max` |
| normalize_whitespace | string.normalize_whitespace | — | every run of whitespace (`\s` under /u, no-break and other Unicode spaces included) becomes one space; ends trimmed; invalid UTF-8 becomes `?` first |
| normalize_line_endings | string.normalize_line_endings | — | CRLF and CR become LF |
| pad | string.pad | `length` (int, required), `pad` (non-empty string, default space), `side` (`right` default, `left`, `both`) | mb_str_pad to `length` characters; longer strings unchanged |
| replace | string.replace | `search` (non-empty string, required), `replace` (string, default empty) | str_replace, case-sensitive |
| regex_replace | string.regex_replace | `pattern` (PCRE with delimiters, required, must compile), `replacement` (string, default empty; `$1`, `\1`) | preg_replace; the empty string when PCRE fails on the input |
| strip_non_printable | string.strip_non_printable | — | removes C0 controls except tab, LF and CR; DEL; C1 controls; U+200B, U+200E, U+200F, U+202A–U+202E, U+2066–U+2069, U+FEFF; keeps U+200D; invalid UTF-8 becomes `?` first |

## 2. Html

| Alias | Name | Parameters | Result for strings |
|---|---|---|---|
| strip_tags | html.strip_tags | `allowed` (string `'<b><i>'` or list of tag names) | removes `script` and `style` elements with their content, then the tags strip_tags() recognizes except the allowed ones, which keep their attributes |
| html_encode | html.encode | `flags` (int, default ENT_QUOTES \| ENT_SUBSTITUTE \| ENT_HTML5), `encoding` (a charset htmlspecialchars() supports, any case, default UTF-8), `double_encode` (bool, default true) | htmlspecialchars() |
| html_decode | html.decode | `flags`, `encoding` as html_encode | html_entity_decode(); can create markup — never output the result unencoded |
| html_purify | html.purify | none; `allowed` is refused | decodes entities (ENT_QUOTES \| ENT_SUBSTITUTE \| ENT_HTML5, UTF-8), removes `script` and `style` elements with their content, removes every tag, trims; nothing is decoded afterwards; the result is text and still needs encoding for HTML output |
| url_encode | html.url_encode | `raw` (bool, default false) | urlencode() (spaces as `+`), or rawurlencode() (RFC 3986, `%20`) with `raw` true |

The empty string comes back when PCRE gives up on hostile input in
`strip_tags` and `html_purify` (ADR-005).

## 3. Numeric

Numbers are read by one reader: ints, finite floats and numeric strings
(integers stay ints, `" 12 "` included; "1.5", "1e3" and "012" become
floats); booleans, INF, NAN and other strings are not numbers.

| Alias | Name | Parameters | Result for numbers |
|---|---|---|---|
| to_int | numeric.to_int | — | int when integral and within the int range ("42", 42.0, "1e3" → 1000); fractions and values beyond the range unchanged |
| to_float | numeric.to_float | — | float |
| clamp | numeric.clamp | `min`, `max` (finite numbers, at least one, `min` ≤ `max`) | `min` below it, `max` above it (the bound as given), else the number; numeric strings become numbers |
| round | numeric.round | `precision` (int, default 2; negative for tens, hundreds), `mode` (`round` half away from zero, `ceil`, `floor`) | a float, with PHP 8.4's RoundingMode (`round(1.1, 2)` with `ceil` stays 1.1) |

## 4. Type

| Alias | Name | Parameters | Result |
|---|---|---|---|
| to_bool | type.to_bool | — | strings "1", "true", "yes", "on" → true; "0", "false", "no", "off", "" → false (case and surrounding whitespace ignored); numbers → true unless zero; other strings and types unchanged |
| to_string | type.to_string | — | ints, floats and booleans ("1", "") with PHP's conversion, `Stringable` with `__toString()`; strings, arrays, null and other objects unchanged |
| to_array | type.to_array | — | arrays unchanged, null → `[]`, anything else → `[value]` |

## 5. Date

| Alias | Name | Parameters | Result |
|---|---|---|---|
| normalize_date | date.normalize | `from` (default `d/m/Y`), `to` (default `Y-m-d`), DateTime formats | parsed strictly with `!` (unnamed fields zero) and written in `to`; impossible dates (31/02), trailing data and other strings unchanged |
| timestamp_to_date | date.timestamp_to_date | `format` (default `Y-m-d H:i:s`), `timezone` (identifier, default UTC) | numbers and numeric strings as seconds since the epoch, a fraction kept as microseconds; outside the years 1 to 9999 unchanged |

## 6. Filter

| Alias | Name | Parameters | Result for strings |
|---|---|---|---|
| digits_only | filter.digits_only | — | keeps 0–9 (ASCII) |
| alpha_only | filter.alpha_only | — | keeps `\pL`, letters of any script; invalid UTF-8 dropped |
| alphanumeric_only | filter.alphanumeric_only | — | keeps `\pL` and `\pN`; invalid UTF-8 dropped |
| email_filter | filter.email | — | strtolower, then FILTER_SANITIZE_EMAIL: whitespace anywhere and everything outside printable ASCII removed ("josé@x.com" → "jos@x.com"); not a validation |

## 7. Brazilian

| Alias | Name | Parameters | Result for strings |
|---|---|---|---|
| format_cpf | brazilian.format_cpf | — | exactly 11 digits after removing the rest → `000.000.000-00`; else unchanged |
| format_cnpj | brazilian.format_cnpj | — | letters and digits kept, letters upper-cased; 12 characters `[0-9A-Z]` then 2 digits → `AA.AAA.AAA/AAAA-00` (IN RFB nº 2.229/2024); else unchanged |
| format_cep | brazilian.format_cep | — | exactly 8 digits after removing the rest → `00000-000`; else unchanged |

The formatters do not check digits; validate with kariricode/validator's
`cpf`, `cnpj` and `cep` (ADR-008).
