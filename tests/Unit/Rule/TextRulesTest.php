<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Tests\Unit\Rule;

use KaririCode\Sanitizer\Contract\SanitizationRule;
use KaririCode\Sanitizer\Core\HtmlCharset;
use KaririCode\Sanitizer\Exception\InvalidRuleException;
use KaririCode\Sanitizer\Exception\SanitizerCode;
use KaririCode\Sanitizer\Rule\Html\HtmlDecodeRule;
use KaririCode\Sanitizer\Rule\Html\HtmlEncodeRule;
use KaririCode\Sanitizer\Rule\Html\HtmlPurifyRule;
use KaririCode\Sanitizer\Rule\Html\StripTagsRule;
use KaririCode\Sanitizer\Rule\Html\UrlEncodeRule;
use KaririCode\Sanitizer\Rule\String\CapitalizeRule;
use KaririCode\Sanitizer\Rule\String\LowerCaseRule;
use KaririCode\Sanitizer\Rule\String\NormalizeLineEndingsRule;
use KaririCode\Sanitizer\Rule\String\NormalizeWhitespaceRule;
use KaririCode\Sanitizer\Rule\String\PadRule;
use KaririCode\Sanitizer\Rule\String\RegexReplaceRule;
use KaririCode\Sanitizer\Rule\String\ReplaceRule;
use KaririCode\Sanitizer\Rule\String\SlugRule;
use KaririCode\Sanitizer\Rule\String\StripNonPrintableRule;
use KaririCode\Sanitizer\Rule\String\TrimRule;
use KaririCode\Sanitizer\Rule\String\TruncateRule;
use KaririCode\Sanitizer\Rule\String\UpperCaseRule;
use KaririCode\Sanitizer\Tests\Support\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

// ── Unit: TextRulesTest ──────────────────────────────────────────────
// The string and HTML rules: exact output, other types untouched.
// ─────────────────────────────────────────────────────────────────────

#[CoversClass(TrimRule::class)]
#[CoversClass(LowerCaseRule::class)]
#[CoversClass(UpperCaseRule::class)]
#[CoversClass(CapitalizeRule::class)]
#[CoversClass(SlugRule::class)]
#[CoversClass(TruncateRule::class)]
#[CoversClass(NormalizeWhitespaceRule::class)]
#[CoversClass(NormalizeLineEndingsRule::class)]
#[CoversClass(PadRule::class)]
#[CoversClass(ReplaceRule::class)]
#[CoversClass(RegexReplaceRule::class)]
#[CoversClass(StripNonPrintableRule::class)]
#[CoversClass(StripTagsRule::class)]
#[CoversClass(HtmlEncodeRule::class)]
#[CoversClass(HtmlDecodeRule::class)]
#[CoversClass(HtmlPurifyRule::class)]
#[CoversClass(UrlEncodeRule::class)]
#[CoversClass(HtmlCharset::class)]
final class TextRulesTest extends RuleTestCase
{
    /**
     * @return iterable<string, array{mixed, SanitizationRule, mixed, 3?: array<string, mixed>}>
     */
    public static function cases(): iterable
    {
        yield 'trim: whitespace' => ["a b", new TrimRule(), " \t a b \n\0\x0B"];
        yield 'trim: characters given' => ['42', new TrimRule(), '0042', ['characters' => '0']];
        yield 'trim: range syntax' => ['5', new TrimRule(), 'ab5ba', ['characters' => 'a..b']];
        yield 'trim: not a string' => [7, new TrimRule(), 7];
        yield 'lower_case: accented' => ['josé ação', new LowerCaseRule(), 'JOSÉ AÇÃO'];
        yield 'lower_case: not a string' => [null, new LowerCaseRule(), null];
        yield 'upper_case: accented' => ['JOSÉ', new UpperCaseRule(), 'josé'];
        yield 'upper_case: not a string' => [1.5, new UpperCaseRule(), 1.5];
        yield 'capitalize: words' => ['José Da Silva', new CapitalizeRule(), 'josé da silva'];
        yield 'capitalize: accented first letters' => ['Élise Ávila', new CapitalizeRule(), 'élise ávila'];
        yield 'capitalize: the rest lower case' => ['José Mcdonald', new CapitalizeRule(), 'JOSÉ McDONALD'];
        yield 'capitalize: not a string' => [true, new CapitalizeRule(), true];

        yield 'slug: accents and punctuation' => ['sao-paulo-brasil', new SlugRule(), 'São Paulo, Brasil!'];
        yield 'slug: other European letters' => ['lodz-strasse-aeon', new SlugRule(), 'Łódź Straße Æon'];
        yield 'slug: separator' => ['my_post_2', new SlugRule(), 'My Post 2', ['separator' => '_']];
        yield 'slug: separator taken literally' => ['a$1b', new SlugRule(), 'a  b', ['separator' => '$1']];
        yield 'slug: empty separator' => ['ab', new SlugRule(), 'a b', ['separator' => '']];
        yield 'slug: nothing left' => ['', new SlugRule(), '!!!'];
        yield 'slug: other scripts dropped' => ['x', new SlugRule(), 'Ωx'];
        yield 'slug: not a string' => [5, new SlugRule(), 5];

        yield 'truncate: short enough' => ['abcde', new TruncateRule(), 'abcde', ['max' => 5]];
        yield 'truncate: cut with suffix' => ['ab...', new TruncateRule(), 'abcdefghij', ['max' => 5]];
        yield 'truncate: characters, not bytes' => ['açã…', new TruncateRule(), 'ação ótima', ['max' => 4, 'suffix' => '…']];
        yield 'truncate: multibyte within max' => ['ação', new TruncateRule(), 'ação', ['max' => 5]];
        yield 'truncate: no suffix' => ['açã', new TruncateRule(), 'ação', ['max' => 3, 'suffix' => '']];
        yield 'truncate: suffix as long as max' => ['...', new TruncateRule(), 'abcd', ['max' => 3]];
        yield 'truncate: default max' => [str_repeat('a', 252) . '...', new TruncateRule(), str_repeat('a', 256)];
        yield 'truncate: default max, exactly' => [str_repeat('a', 255), new TruncateRule(), str_repeat('a', 255)];
        yield 'truncate: not a string' => [123456, new TruncateRule(), 123456, ['max' => 3]];

        yield 'normalize_whitespace: runs and ends' => ['a b c', new NormalizeWhitespaceRule(), "  a \t\n b    c  "];
        yield 'normalize_whitespace: Unicode spaces' => ['a b', new NormalizeWhitespaceRule(), "\u{00A0}a\u{2003}b\u{3000}"];
        yield 'normalize_whitespace: invalid UTF-8' => ['a ? b', new NormalizeWhitespaceRule(), "a \xff b"];
        yield 'normalize_whitespace: not a string' => [[], new NormalizeWhitespaceRule(), []];
        yield 'normalize_line_endings: CRLF and CR' => ["a\nb\nc\n", new NormalizeLineEndingsRule(), "a\r\nb\rc\n"];
        yield 'normalize_line_endings: not a string' => [1, new NormalizeLineEndingsRule(), 1];

        yield 'pad: right by default' => ['ab   ', new PadRule(), 'ab', ['length' => 5]];
        yield 'pad: left, multibyte' => ['**ção', new PadRule(), 'ção', ['length' => 5, 'pad' => '*', 'side' => 'left']];
        yield 'pad: both' => ['-ab--', new PadRule(), 'ab', ['length' => 5, 'pad' => '-', 'side' => 'both']];
        yield 'pad: already long' => ['abcdef', new PadRule(), 'abcdef', ['length' => 3]];
        yield 'pad: not a string' => [42, new PadRule(), 42, ['length' => 5]];

        yield 'replace: every occurrence' => ['88999990000', new ReplaceRule(), '88 99999 0000', ['search' => ' ']];
        yield 'replace: with a replacement' => ['a-b-c', new ReplaceRule(), 'a b c', ['search' => ' ', 'replace' => '-']];
        yield 'replace: case-sensitive' => ['Aa', new ReplaceRule(), 'Aa', ['search' => 'x']];
        yield 'replace: not a string' => [false, new ReplaceRule(), false, ['search' => 'x']];

        yield 'regex_replace: matches' => ['ana_1_', new RegexReplaceRule(), 'ana-1!', ['pattern' => '/[^a-z0-9_]/i', 'replacement' => '_']];
        yield 'regex_replace: removes by default' => ['ana1', new RegexReplaceRule(), 'ana-1!', ['pattern' => '/[^a-z0-9]/i']];
        yield 'regex_replace: groups' => ['2026-11-28', new RegexReplaceRule(), '28/11/2026', ['pattern' => '#\A(\d{2})/(\d{2})/(\d{4})\z#', 'replacement' => '$3-$2-$1']];
        yield 'regex_replace: input PCRE cannot read' => ['', new RegexReplaceRule(), "\xffab", ['pattern' => '/a/u']];
        yield 'regex_replace: not a string' => [5, new RegexReplaceRule(), 5, ['pattern' => '/5/']];

        yield 'strip_non_printable: controls and bidi' => ["a\tb\nc\rd", new StripNonPrintableRule(), "a\tb\nc\rd\x00\x07\x1B\x7F\u{0085}\u{202E}\u{2066}\u{200E}\u{200F}\u{200B}\u{FEFF}"];
        yield 'strip_non_printable: joiners stay' => ["👩\u{200D}💻", new StripNonPrintableRule(), "👩\u{200D}💻"];
        yield 'strip_non_printable: invalid UTF-8' => ['a?', new StripNonPrintableRule(), "a\xff"];
        yield 'strip_non_printable: not a string' => [null, new StripNonPrintableRule(), null];

        yield 'strip_tags: everything' => ['xz', new StripTagsRule(), '<b>x</b><script>alert(1)</script><style>p{}</style><i>z</i>'];
        yield 'strip_tags: allowed as a list' => ['<b>x</b>z', new StripTagsRule(), '<b>x</b><i>z</i>', ['allowed' => ['b']]];
        yield 'strip_tags: allowed as a string' => ['<i>z</i>', new StripTagsRule(), '<b></b><i>z</i>', ['allowed' => '<i>']];
        yield 'strip_tags: script content goes, unclosed text stays' => ['y', new StripTagsRule(), '<SCRIPT type="x">alert(1)</SCRIPT >y'];
        yield 'strip_tags: not a string' => [1, new StripTagsRule(), 1];

        yield 'html_encode: special characters' => ['&lt;a href=&quot;x&quot;&gt;&apos;&amp;&lt;/a&gt;', new HtmlEncodeRule(), '<a href="x">\'&</a>'];
        yield 'html_encode: entities encoded again by default' => ['&amp;amp;', new HtmlEncodeRule(), '&amp;'];
        yield 'html_encode: no double encoding' => ['&amp; &lt;', new HtmlEncodeRule(), '&amp; <', ['double_encode' => false]];
        yield 'html_encode: flags' => ['"\'', new HtmlEncodeRule(), '"\'', ['flags' => ENT_NOQUOTES]];
        yield 'html_encode: other charset' => ['&lt;', new HtmlEncodeRule(), '<', ['encoding' => 'iso-8859-1']];
        yield 'html_encode: invalid UTF-8 substituted' => ["\u{FFFD}", new HtmlEncodeRule(), "\xff"];
        yield 'html_encode: not a string' => [5, new HtmlEncodeRule(), 5];
        yield 'html_decode: entities' => ['<b>&amp;\'', new HtmlDecodeRule(), '&lt;b&gt;&amp;amp;&apos;'];
        yield 'html_decode: other charset, any case' => ['<é', new HtmlDecodeRule(), '&lt;é', ['encoding' => 'utf-8']];
        yield 'html_decode: flags' => ['&apos;', new HtmlDecodeRule(), '&apos;', ['flags' => ENT_NOQUOTES]];
        yield 'html_decode: not a string' => [5, new HtmlDecodeRule(), 5];

        yield 'html_purify: tags and scripts' => ['Hi you', new HtmlPurifyRule(), '<p>Hi &lt;b&gt;you&lt;/b&gt;</p><script>alert(1)</script>'];
        yield 'html_purify: encoded script' => ['', new HtmlPurifyRule(), '&lt;script&gt;alert(1)&lt;/script&gt;'];
        yield 'html_purify: encoded twice stays text' => ['&lt;b&gt;', new HtmlPurifyRule(), '&amp;lt;b&amp;gt;'];
        yield 'html_purify: text with a less-than sign' => ['a < b', new HtmlPurifyRule(), ' a &lt; b '];
        yield 'html_purify: quotes and apostrophes decoded' => ['"hi" \'you\'', new HtmlPurifyRule(), '&quot;hi&quot; &apos;you&apos;'];
        yield 'html_purify: not a string' => [[1], new HtmlPurifyRule(), [1]];

        yield 'url_encode: query' => ['a+b%26c%2Fd', new UrlEncodeRule(), 'a b&c/d'];
        yield 'url_encode: raw' => ['a%20b%26c', new UrlEncodeRule(), 'a b&c', ['raw' => true]];
        yield 'url_encode: not a string' => [5, new UrlEncodeRule(), 5];
    }

    /**
     * @param array<string, mixed> $parameters
     */
    #[Test]
    #[DataProvider('cases')]
    public function rulesProduceTheirOutput(mixed $expected, SanitizationRule $rule, mixed $value, array $parameters = []): void
    {
        self::assertSanitizes($expected, $rule, $value, $parameters);
    }

    #[Test]
    public function rulesNameThemselves(): void
    {
        $names = array_map(static fn(SanitizationRule $rule): string => $rule->name(), [
            new TrimRule(), new LowerCaseRule(), new UpperCaseRule(), new CapitalizeRule(), new SlugRule(), new TruncateRule(),
            new NormalizeWhitespaceRule(), new NormalizeLineEndingsRule(), new PadRule(), new ReplaceRule(), new RegexReplaceRule(),
            new StripNonPrintableRule(), new StripTagsRule(), new HtmlEncodeRule(), new HtmlDecodeRule(), new HtmlPurifyRule(), new UrlEncodeRule(),
        ]);

        self::assertSame([
            'string.trim', 'string.lower_case', 'string.upper_case', 'string.capitalize', 'string.slug', 'string.truncate',
            'string.normalize_whitespace', 'string.normalize_line_endings', 'string.pad', 'string.replace', 'string.regex_replace',
            'string.strip_non_printable', 'html.strip_tags', 'html.encode', 'html.decode', 'html.purify', 'html.url_encode',
        ], $names);
    }

    /**
     * @return iterable<string, array{SanitizerCode, SanitizationRule, array<string, mixed>, non-empty-string}>
     */
    public static function misconfigurations(): iterable
    {
        $invalid = SanitizerCode::InvalidRuleParameter;
        $missing = SanitizerCode::MissingRuleParameter;
        yield 'trim characters not a string' => [$invalid, new TrimRule(), ['characters' => 1], 'must be a string, int given.'];
        yield 'slug separator not a string' => [$invalid, new SlugRule(), ['separator' => 1], 'must be a string, int given.'];
        yield 'truncate max below the suffix' => [$invalid, new TruncateRule(), ['max' => 2], 'must be at least the length of the suffix, 2 given.'];
        yield 'truncate max not an integer' => [$invalid, new TruncateRule(), ['max' => '5'], 'must be an integer, string given.'];
        yield 'pad without length' => [$missing, new PadRule(), [], 'needs the parameter "length".'];
        yield 'pad with an empty pad' => [$invalid, new PadRule(), ['length' => 5, 'pad' => ''], 'must be a non-empty string.'];
        yield 'pad on a side that is not one' => [$invalid, new PadRule(), ['length' => 5, 'side' => 'center'], 'must be left, right or both.'];
        yield 'replace without search' => [$missing, new ReplaceRule(), [], 'needs the parameter "search".'];
        yield 'replace with an empty search' => [$invalid, new ReplaceRule(), ['search' => ''], 'must be a non-empty string.'];
        yield 'replace with a list replacement' => [$invalid, new ReplaceRule(), ['search' => 'a', 'replace' => ['b']], 'must be a string, array given.'];
        yield 'regex_replace without pattern' => [$missing, new RegexReplaceRule(), [], 'needs the parameter "pattern".'];
        yield 'regex_replace with an empty pattern' => [$invalid, new RegexReplaceRule(), ['pattern' => ''], 'must be a regular expression that compiles.'];
        yield 'regex_replace with a pattern without delimiters' => [$invalid, new RegexReplaceRule(), ['pattern' => 'a+'], 'must be a regular expression that compiles.'];
        yield 'regex_replace with a pattern that does not compile' => [$invalid, new RegexReplaceRule(), ['pattern' => '/(/'], 'must be a regular expression that compiles.'];
        yield 'strip_tags allowed as a number' => [$invalid, new StripTagsRule(), ['allowed' => 5], 'must be a string or a list of tag names, int given.'];
        yield 'strip_tags allowed with a number' => [$invalid, new StripTagsRule(), ['allowed' => ['b', 1]], 'must be a string or a list of tag names, array given.'];
        yield 'strip_tags allowed as a map' => [$invalid, new StripTagsRule(), ['allowed' => ['b' => 'b']], 'must be a string or a list of tag names, array given.'];
        yield 'html_encode with an unknown charset' => [$invalid, new HtmlEncodeRule(), ['encoding' => 'UTF-7'], "must be a charset htmlspecialchars() supports, 'UTF-7' given."];
        yield 'html_encode double_encode not a boolean' => [$invalid, new HtmlEncodeRule(), ['double_encode' => 1], 'must be a boolean, int given.'];
        yield 'html_encode flags not an integer' => [$invalid, new HtmlEncodeRule(), ['flags' => 'ENT_QUOTES'], 'must be an integer, string given.'];
        yield 'html_decode with an unknown charset' => [$invalid, new HtmlDecodeRule(), ['encoding' => 'latin9'], "must be a charset htmlspecialchars() supports, 'latin9' given."];
        yield 'html_purify with allowed tags' => [$invalid, new HtmlPurifyRule(), ['allowed' => '<b>'], 'must be absent: html_purify keeps no tag; use strip_tags to keep some.'];
        yield 'url_encode raw not a boolean' => [$invalid, new UrlEncodeRule(), ['raw' => 'yes'], 'must be a boolean, string given.'];
    }

    /**
     * Misconfiguration is reported whatever the value, so a wrong rule
     * cannot hide behind input of another type.
     *
     * @param array<string, mixed> $parameters
     * @param non-empty-string     $ending
     */
    #[Test]
    #[DataProvider('misconfigurations')]
    public function misconfiguredRulesThrow(SanitizerCode $code, SanitizationRule $rule, array $parameters, string $ending): void
    {
        self::assertRefused($code, $rule, $parameters);

        $this->expectException(InvalidRuleException::class);
        $this->expectExceptionMessageMatches('/' . preg_quote($ending, '/') . '\z/');
        $rule->sanitize(12, self::context($parameters));
    }
}
