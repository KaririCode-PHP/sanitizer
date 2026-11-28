# ADR-008: Lossless Conversions, Strict Dates, Unicode-Aware Filters and the Alphanumeric CNPJ

## Status

Accepted — V2.0.0

## Date

2026-11-28

## Context

A sanitizer that changes the meaning of a value is worse than none: the
change is silent and the validator that runs next checks the wrong
thing. The earlier rules did that in several places:

- `to_int` cast with `(int)`: "12.9" became 12, "1e30" an arbitrary
  integer. `to_bool` turned INF into true.
- `clamp` cast the bound to the value's type, so an integer clamped
  to a maximum of 9.5 came back as 9.
- `normalize_date` called `createFromFormat()` without `!`: the fields
  the format did not name took the current time, and dates PHP rolls
  over were accepted (31/02/2026 became 2026-03-03).
- `pad` counted bytes (`str_pad()` on UTF-8), and `truncate` with a
  `max` shorter than its suffix returned a string longer than `max`.
- `strip_non_printable` removed ASCII controls only: C1 controls,
  zero-width characters and the bidirectional overrides behind Trojan
  Source (CVE-2021-42574) passed.
- `format_cnpj` kept digits only. Under IN RFB nº 2.229/2024 the twelve
  characters of root and order may be letters (the Receita Federal
  issues such numbers from July 2026), and removing them yields a
  different, wrong number.

### Prior Art

- kariricode/validator ADR-008: the alphanumeric CNPJ, all 26 letters,
  upper case.
- Unicode TR #9 (bidirectional algorithm) and TR #36 (security
  considerations).

## Decision

1. **Numbers convert only without loss.** `to_int` converts integral
   numbers within the integer range ("42", 42.0, "1e3"); fractions,
   values beyond the range, INF, NAN and booleans are returned
   unchanged. `to_float`, `round`, `clamp` and `to_bool` read numbers
   through the same reader (finite int or float, numeric strings, never
   booleans). `clamp` returns the bound as given, or the number.
2. **Dates are read strictly.** `normalize_date` parses with `!` and
   refuses dates PHP would move or strings with trailing data, leaving
   them unchanged; `timestamp_to_date` accepts the years 1 to 9999,
   keeps a fraction as microseconds and checks its time zone.
3. **Text rules count characters.** `truncate`, `pad` and the case rules
   work on UTF-8 characters; `truncate` refuses a `max` shorter than its
   suffix.
4. **Filters are Unicode-aware.** `strip_non_printable` removes C0
   controls except tab, line feed and carriage return, DEL, the C1
   controls, U+200B, U+200E, U+200F, U+202A–U+202E, U+2066–U+2069 and
   U+FEFF, and keeps U+200D, which joins emoji sequences.
   `alpha_only` and `alphanumeric_only` keep letters and numbers of
   any script; `digits_only` keeps ASCII digits, the ones documents and
   phone numbers use. Invalid UTF-8 is replaced before Unicode rules run.
5. **Brazilian formats.** `format_cpf` and `format_cep` format exactly
   11 and 8 digits; `format_cnpj` formats twelve characters from 0–9
   and A–Z followed by two digits, upper-casing letters. None of them
   validates check digits; a value of another shape is returned
   unchanged for the validator to reject.

## Consequences

### Positive

- A sanitized value means what the input meant; anything the rules
  could not convert reaches validation as it was typed.
- Alphanumeric CNPJs survive formatting.
- Text pasted with hidden direction overrides is cleaned.

### Negative

- **Breaking:** `to_int("12.9")` now returns "12.9", and code that
  relied on truncation must round first (`round` with `precision` 0,
  then `to_int`).
- `strip_non_printable` removes more than before, including the
  left-to-right and right-to-left marks some right-to-left text uses.
- `email_filter` keeps ASCII only (FILTER_SANITIZE_EMAIL), so
  internationalized addresses lose characters; the rule says so.

## Alternatives Considered

- **Round in `to_int`:** a choice the application should make visibly,
  with `round` and its mode.
- **Keep U+200E and U+200F:** friendlier to right-to-left text, and they
  are the marks used to disguise file names and identifiers.
- **Validate check digits in the formatters:** the validator's job;
  formatting an invalid number visibly is more useful than refusing it
  silently.

## References

- IN RFB nº 2.229/2024; kariricode/validator ADR-008.
- Unicode TR #9, TR #36; CVE-2021-42574.
- SPEC-002 §1, §3–§7.
