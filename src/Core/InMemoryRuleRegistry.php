<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Core;

use KaririCode\Sanitizer\Contract\RuleRegistry;
use KaririCode\Sanitizer\Contract\SanitizationRule;
use KaririCode\Sanitizer\Exception\InvalidRuleException;
use KaririCode\Sanitizer\Rule\Brazilian\FormatCepRule;
use KaririCode\Sanitizer\Rule\Brazilian\FormatCnpjRule;
use KaririCode\Sanitizer\Rule\Brazilian\FormatCpfRule;
use KaririCode\Sanitizer\Rule\Date\NormalizeDateRule;
use KaririCode\Sanitizer\Rule\Date\TimestampToDateRule;
use KaririCode\Sanitizer\Rule\Filter\AlphanumericOnlyRule;
use KaririCode\Sanitizer\Rule\Filter\AlphaOnlyRule;
use KaririCode\Sanitizer\Rule\Filter\DigitsOnlyRule;
use KaririCode\Sanitizer\Rule\Filter\EmailFilterRule;
use KaririCode\Sanitizer\Rule\Html\HtmlDecodeRule;
use KaririCode\Sanitizer\Rule\Html\HtmlEncodeRule;
use KaririCode\Sanitizer\Rule\Html\HtmlPurifyRule;
use KaririCode\Sanitizer\Rule\Html\StripTagsRule;
use KaririCode\Sanitizer\Rule\Html\UrlEncodeRule;
use KaririCode\Sanitizer\Rule\Numeric\ClampRule;
use KaririCode\Sanitizer\Rule\Numeric\RoundRule;
use KaririCode\Sanitizer\Rule\Numeric\ToFloatRule;
use KaririCode\Sanitizer\Rule\Numeric\ToIntRule;
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
use KaririCode\Sanitizer\Rule\Type\ToArrayRule;
use KaririCode\Sanitizer\Rule\Type\ToBoolRule;
use KaririCode\Sanitizer\Rule\Type\ToStringRule;

// ── Core: InMemoryRuleRegistry ───────────────────────────────────────
// The RuleRegistry the engine uses unless given another.
// ─────────────────────────────────────────────────────────────────────

/**
 * Keeps rules by alias in memory. withDefaults() registers the 33
 * built-in rules; applications add their own under aliases of their
 * choice.
 *
 * Preconditions: an alias is a non-empty string, registered once.
 *
 * Thread-safety: register before sharing; resolution only reads.
 *
 * Side effects: register() adds to the registry.
 *
 * Example:
 *
 *     $registry = InMemoryRuleRegistry::withDefaults();
 *     $registry->register('collapse_dashes', new CollapseDashesRule());
 *
 * References: ADR-001; SPEC-002.
 */
final class InMemoryRuleRegistry implements RuleRegistry
{
    /** @var array<string, SanitizationRule> */
    private array $rules = [];

    public static function withDefaults(): self
    {
        $registry = new self();

        foreach (self::defaults() as $alias => $rule) {
            $registry->register($alias, $rule);
        }

        return $registry;
    }

    #[\Override]
    public function register(string $alias, SanitizationRule $rule): void
    {
        if ($alias === '') {
            throw InvalidRuleException::emptyAlias();
        }

        if (isset($this->rules[$alias])) {
            throw InvalidRuleException::duplicateAlias($alias);
        }

        $this->rules[$alias] = $rule;
    }

    #[\Override]
    public function resolve(string $alias): SanitizationRule
    {
        return $this->rules[$alias] ?? throw InvalidRuleException::unknownAlias($alias);
    }

    #[\Override]
    public function has(string $alias): bool
    {
        return isset($this->rules[$alias]);
    }

    #[\Override]
    public function aliases(): array
    {
        return array_keys($this->rules);
    }

    /**
     * @return array<string, SanitizationRule>
     */
    private static function defaults(): array
    {
        return [
            'trim' => new TrimRule(),
            'lower_case' => new LowerCaseRule(),
            'upper_case' => new UpperCaseRule(),
            'capitalize' => new CapitalizeRule(),
            'slug' => new SlugRule(),
            'truncate' => new TruncateRule(),
            'normalize_whitespace' => new NormalizeWhitespaceRule(),
            'normalize_line_endings' => new NormalizeLineEndingsRule(),
            'pad' => new PadRule(),
            'replace' => new ReplaceRule(),
            'regex_replace' => new RegexReplaceRule(),
            'strip_non_printable' => new StripNonPrintableRule(),
            'strip_tags' => new StripTagsRule(),
            'html_encode' => new HtmlEncodeRule(),
            'html_decode' => new HtmlDecodeRule(),
            'html_purify' => new HtmlPurifyRule(),
            'url_encode' => new UrlEncodeRule(),
            'to_int' => new ToIntRule(),
            'to_float' => new ToFloatRule(),
            'clamp' => new ClampRule(),
            'round' => new RoundRule(),
            'to_bool' => new ToBoolRule(),
            'to_string' => new ToStringRule(),
            'to_array' => new ToArrayRule(),
            'normalize_date' => new NormalizeDateRule(),
            'timestamp_to_date' => new TimestampToDateRule(),
            'digits_only' => new DigitsOnlyRule(),
            'alpha_only' => new AlphaOnlyRule(),
            'alphanumeric_only' => new AlphanumericOnlyRule(),
            'email_filter' => new EmailFilterRule(),
            'format_cpf' => new FormatCpfRule(),
            'format_cnpj' => new FormatCnpjRule(),
            'format_cep' => new FormatCepRule(),
        ];
    }
}
