<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Core;

use KaririCode\Sanitizer\Contract\SanitizationContext;
use KaririCode\Sanitizer\Exception\InvalidRuleException;

// ── Core: HtmlCharset ────────────────────────────────────────────────
// Reads and checks the 'encoding' parameter of the HTML rules.
// ─────────────────────────────────────────────────────────────────────

/**
 * Reads the 'encoding' parameter of html_encode and html_decode (UTF-8
 * unless set) and accepts it only when htmlspecialchars() and
 * html_entity_decode() support it, in any letter case, so a misspelled
 * charset is reported as the application's mistake before any value is
 * touched instead of surfacing as PHP's ValueError on the first string.
 *
 * Preconditions: the context belongs to an HTML rule.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     HtmlCharset::of($context); // 'UTF-8', or the 'encoding' given
 *
 * References: PHP htmlspecialchars(), supported charsets; ADR-005; SPEC-002 §2.
 */
final class HtmlCharset
{
    /** @var list<string> the charsets htmlspecialchars() and html_entity_decode() accept, upper case */
    private const array CHARSETS = ['UTF-8', 'ISO-8859-1', 'ISO8859-1', 'ISO-8859-5', 'ISO8859-5', 'ISO-8859-15', 'ISO8859-15', 'CP866', 'IBM866', '866', 'CP1251', 'WINDOWS-1251', 'WIN-1251', '1251', 'CP1252', 'WINDOWS-1252', '1252', 'KOI8-R', 'KOI8-RU', 'KOI8R', 'BIG5', '950', 'GB2312', '936', 'BIG5-HKSCS', 'SHIFT_JIS', 'SJIS', 'SJIS-WIN', 'CP932', '932', 'EUC-JP', 'EUCJP', 'EUCJP-WIN', 'MACROMAN'];

    public static function of(SanitizationContext $context): string
    {
        $encoding = $context->stringParameter('encoding', 'UTF-8');

        if (!in_array(needle: strtoupper($encoding), haystack: self::CHARSETS, strict: true)) {
            throw InvalidRuleException::invalidParameter(field: $context->field, parameter: 'encoding', expected: 'a charset htmlspecialchars() supports', given: var_export($encoding, true));
        }

        return $encoding;
    }
}
