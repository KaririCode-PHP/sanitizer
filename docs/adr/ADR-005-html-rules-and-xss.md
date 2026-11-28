# ADR-005: HTML Rules — Decode Before Stripping, Remove Script Content, Encode at Output

## Status

Accepted — V2.0.0

## Date

2026-11-28

## Context

The package offers four HTML rules and both earlier lines described
them as layers of XSS defense. Two of them did not hold:

- `html_purify` ran `strip_tags()` first and `html_entity_decode()`
  second. Markup written as entities survives the first step as text and
  is turned into markup by the second:
  `&lt;script&gt;alert(1)&lt;/script&gt;` came out as
  `<script>alert(1)</script>`. The rule documented as the most
  aggressive produced the payload it was meant to remove.
- `strip_tags` (the PHP function and the rule) removes tags but keeps
  what is between them, so `<script>alert(1)</script>` became the text
  `alert(1)`, and `<style>` blocks became CSS in the text.

No sanitizer that keeps markup is safe without an HTML parser and an
allow-list policy (HTML Purifier, Symfony HtmlSanitizer); this package
has no dependency for that and should not pretend to be one.

### Prior Art

- OWASP XSS Prevention Cheat Sheet: output encoding for the context is
  the primary defense; sanitizing HTML input needs a dedicated library.
- OWASP ASVS 5.0 V1.1.1 (decode once, into canonical form, before any
  further processing), V1.1.2 (encode as the last step, for the
  interpreter) and V1.3.1 (untrusted HTML goes through a well-known
  sanitization library).

## Decision

1. `html_purify` makes **text** out of HTML: it decodes entities first
   (ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, UTF-8), removes `script`
   and `style` elements with their content, removes every remaining tag
   with `strip_tags()`, and trims. Nothing is decoded afterwards. It
   keeps no tag and refuses an `allowed` parameter (SAN_3305).
2. `strip_tags` removes `script` and `style` elements with their
   content, then the tags `strip_tags()` recognizes except the
   `allowed` ones (a string or a list of names). Its documentation says
   that allowed tags keep their attributes, `onclick` and
   `javascript:` URLs included.
3. `html_encode` (htmlspecialchars, ENT_QUOTES | ENT_SUBSTITUTE |
   ENT_HTML5 by default) is the rule for values that will be written
   into HTML; the documentation recommends encoding at output time and
   warns that encoding at input and again at output shows entities.
4. `html_decode` documents that decoding can create markup and must not
   feed output without encoding.
5. The charset of `html_encode` and `html_decode` is checked against
   the list `htmlspecialchars()` supports, in any case, so a typo is
   SAN_3305 instead of PHP's `ValueError` on the first string.
6. When PCRE gives up on hostile input (backtracking limit), the rules
   return the empty string, never the input (ADR-004).

## Consequences

### Positive

- `html_purify`'s output holds no tag a browser would run, for input
  encoded once or not at all.
- The documentation no longer promises what the rules cannot do.

### Negative

- `html_purify`'s output is text and may contain `<`, `>` and `&`
  (`a < b` stays `a < b`; input encoded twice keeps one level of
  entities): it must still be encoded where it is written into HTML.
- Rich text that must keep markup needs an HTML sanitizer library; this
  package does not provide one.
- **Breaking:** `html_purify` no longer accepts `allowed`; values that
  relied on the old order now come out without the decoded markup.

## Alternatives Considered

- **Depend on HTML Purifier or Symfony HtmlSanitizer:** real allow-list
  sanitization, and a dependency most users of the package do not need;
  an application can register either as a custom rule.
- **Decode repeatedly until stable:** removes multiply encoded markup,
  and changes text that legitimately shows entities.
- **Keep the old order and document it:** a rule named "purify" that
  creates script tags cannot be fixed by documentation.

## References

- OWASP XSS Prevention Cheat Sheet; OWASP ASVS 5.0 V1.1.1, V1.1.2,
  V1.3.1.
- PHP manual: strip_tags(), html_entity_decode(), htmlspecialchars().
- SPEC-002 §2; SECURITY.md.
