<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Rule\String;

use KaririCode\Sanitizer\Contract\SanitizationContext;
use KaririCode\Sanitizer\Contract\SanitizationRule;

// ── Rule: SlugRule ───────────────────────────────────────────────────
// Turns a string into a lower-case URL slug.
// ─────────────────────────────────────────────────────────────────────

/**
 * Lower-cases the string, transliterates the Latin letters with
 * diacritics of the European languages (á → a, ç → c, ß → ss, ł → l),
 * then joins the runs of ASCII letters and digits with the
 * 'separator' ("-" unless set). Letters of other scripts are dropped.
 *
 * Preconditions: 'separator', when given, is a string; it is inserted literally.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['slug' => ['slug']]; // 'São Paulo, Brasil!' → 'sao-paulo-brasil'
 *
 * References: SPEC-002 §1.
 */
final readonly class SlugRule implements SanitizationRule
{
    #[\Override]
    public function sanitize(mixed $value, SanitizationContext $context): mixed
    {
        $separator = $context->stringParameter('separator', '-');

        if (!is_string($value)) {
            return $value;
        }

        $words = preg_split(pattern: '/[^a-z0-9]+/', subject: strtr(mb_strtolower($value, 'UTF-8'), self::TRANSLITERATION), flags: PREG_SPLIT_NO_EMPTY);

        return implode($separator, $words === false ? [] : $words);
    }

    #[\Override]
    public function name(): string
    {
        return 'string.slug';
    }

    /** @var array<string, string> lower-case letter => ASCII */
    private const array TRANSLITERATION = [
        'á' => 'a',
        'à' => 'a',
        'ã' => 'a',
        'â' => 'a',
        'ä' => 'a',
        'å' => 'a',
        'ą' => 'a',
        'ă' => 'a',
        'æ' => 'ae',
        'ç' => 'c',
        'ć' => 'c',
        'č' => 'c',
        'ď' => 'd',
        'đ' => 'd',
        'ð' => 'd',
        'é' => 'e',
        'è' => 'e',
        'ê' => 'e',
        'ë' => 'e',
        'ę' => 'e',
        'ě' => 'e',
        'ğ' => 'g',
        'í' => 'i',
        'ì' => 'i',
        'î' => 'i',
        'ï' => 'i',
        'ı' => 'i',
        'ł' => 'l',
        'ľ' => 'l',
        'ñ' => 'n',
        'ń' => 'n',
        'ň' => 'n',
        'ó' => 'o',
        'ò' => 'o',
        'õ' => 'o',
        'ô' => 'o',
        'ö' => 'o',
        'ø' => 'o',
        'ő' => 'o',
        'œ' => 'oe',
        'ř' => 'r',
        'ś' => 's',
        'š' => 's',
        'ş' => 's',
        'ß' => 'ss',
        'ť' => 't',
        'ţ' => 't',
        'þ' => 'th',
        'ú' => 'u',
        'ù' => 'u',
        'û' => 'u',
        'ü' => 'u',
        'ů' => 'u',
        'ű' => 'u',
        'ý' => 'y',
        'ÿ' => 'y',
        'ź' => 'z',
        'ż' => 'z',
        'ž' => 'z',
    ];
}
