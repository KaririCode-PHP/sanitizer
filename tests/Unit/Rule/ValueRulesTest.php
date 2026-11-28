<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Tests\Unit\Rule;

use KaririCode\Sanitizer\Contract\SanitizationRule;
use KaririCode\Sanitizer\Exception\InvalidRuleException;
use KaririCode\Sanitizer\Exception\SanitizerCode;
use KaririCode\Sanitizer\Rule\Brazilian\FormatCepRule;
use KaririCode\Sanitizer\Rule\Brazilian\FormatCnpjRule;
use KaririCode\Sanitizer\Rule\Brazilian\FormatCpfRule;
use KaririCode\Sanitizer\Rule\Date\NormalizeDateRule;
use KaririCode\Sanitizer\Rule\Date\TimestampToDateRule;
use KaririCode\Sanitizer\Rule\Filter\AlphanumericOnlyRule;
use KaririCode\Sanitizer\Rule\Filter\AlphaOnlyRule;
use KaririCode\Sanitizer\Rule\Filter\DigitsOnlyRule;
use KaririCode\Sanitizer\Rule\Filter\EmailFilterRule;
use KaririCode\Sanitizer\Rule\Numeric\ClampRule;
use KaririCode\Sanitizer\Rule\Numeric\RoundRule;
use KaririCode\Sanitizer\Rule\Numeric\ToFloatRule;
use KaririCode\Sanitizer\Rule\Numeric\ToIntRule;
use KaririCode\Sanitizer\Rule\Type\ToArrayRule;
use KaririCode\Sanitizer\Rule\Type\ToBoolRule;
use KaririCode\Sanitizer\Rule\Type\ToStringRule;
use KaririCode\Sanitizer\Tests\Support\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

// ── Unit: ValueRulesTest ─────────────────────────────────────────────
// Numeric, type, date, filter and Brazilian rules: lossless or untouched.
// ─────────────────────────────────────────────────────────────────────

#[CoversClass(ToIntRule::class)]
#[CoversClass(ToFloatRule::class)]
#[CoversClass(ClampRule::class)]
#[CoversClass(RoundRule::class)]
#[CoversClass(ToBoolRule::class)]
#[CoversClass(ToStringRule::class)]
#[CoversClass(ToArrayRule::class)]
#[CoversClass(NormalizeDateRule::class)]
#[CoversClass(TimestampToDateRule::class)]
#[CoversClass(DigitsOnlyRule::class)]
#[CoversClass(AlphaOnlyRule::class)]
#[CoversClass(AlphanumericOnlyRule::class)]
#[CoversClass(EmailFilterRule::class)]
#[CoversClass(FormatCpfRule::class)]
#[CoversClass(FormatCnpjRule::class)]
#[CoversClass(FormatCepRule::class)]
final class ValueRulesTest extends RuleTestCase
{
    /**
     * @return iterable<string, array{mixed, SanitizationRule, mixed, 3?: array<string, mixed>}>
     */
    public static function cases(): iterable
    {
        yield 'to_int: numeric string' => [42, new ToIntRule(), '42'];
        yield 'to_int: surrounding spaces' => [42, new ToIntRule(), ' 42 '];
        yield 'to_int: negative' => [-7, new ToIntRule(), '-7'];
        yield 'to_int: whole float string' => [42, new ToIntRule(), '42.0'];
        yield 'to_int: exponent' => [1000, new ToIntRule(), '1e3'];
        yield 'to_int: whole float' => [3, new ToIntRule(), 3.0];
        yield 'to_int: negative whole float' => [-42, new ToIntRule(), -42.0];
        yield 'to_int: negative fraction kept' => [-0.5, new ToIntRule(), -0.5];
        yield 'to_int: largest integer' => [PHP_INT_MAX, new ToIntRule(), '9223372036854775807'];
        yield 'to_int: smallest integer as a float' => [PHP_INT_MIN, new ToIntRule(), (float) PHP_INT_MIN];
        yield 'to_int: fraction kept' => ['42.5', new ToIntRule(), '42.5'];
        yield 'to_int: fraction kept, float' => [0.1, new ToIntRule(), 0.1];
        yield 'to_int: beyond the integers' => ['9223372036854775808', new ToIntRule(), '9223372036854775808'];
        yield 'to_int: float beyond the integers' => [1.0e19, new ToIntRule(), 1.0e19];
        yield 'to_int: not numeric' => ['12abc', new ToIntRule(), '12abc'];
        yield 'to_int: boolean' => [true, new ToIntRule(), true];
        yield 'to_int: infinity' => [INF, new ToIntRule(), INF];
        yield 'to_float: numeric string' => [3.14, new ToFloatRule(), '3.14'];
        yield 'to_float: integer string' => [10.0, new ToFloatRule(), '10'];
        yield 'to_float: integer' => [7.0, new ToFloatRule(), 7];
        yield 'to_float: not numeric' => ['x', new ToFloatRule(), 'x'];
        yield 'to_float: null' => [null, new ToFloatRule(), null];

        yield 'clamp: above max' => [10, new ClampRule(), 15, ['min' => 0, 'max' => 10]];
        yield 'clamp: below min' => [0, new ClampRule(), -5, ['min' => 0, 'max' => 10]];
        yield 'clamp: inside' => [5, new ClampRule(), 5, ['min' => 0, 'max' => 10]];
        yield 'clamp: on the bound' => [10, new ClampRule(), 10, ['min' => 0, 'max' => 10]];
        yield 'clamp: numeric string becomes a number' => [7, new ClampRule(), '7', ['min' => 0, 'max' => 10]];
        yield 'clamp: float bounds' => [2.0, new ClampRule(), 2.5, ['max' => 2.0]];
        yield 'clamp: an integer on a float max stays an integer' => [10, new ClampRule(), 10, ['max' => 10.0]];
        yield 'clamp: an integer on a float min stays an integer' => [0, new ClampRule(), 0, ['min' => 0.0]];
        yield 'clamp: min only' => [5, new ClampRule(), 3, ['min' => 5]];
        yield 'clamp: max only, inside' => [-3, new ClampRule(), -3, ['max' => 5]];
        yield 'clamp: equal bounds' => [4, new ClampRule(), 9, ['min' => 4, 'max' => 4]];
        yield 'clamp: not numeric' => ['abc', new ClampRule(), 'abc', ['min' => 0]];
        yield 'round: two places by default' => [3.14, new RoundRule(), 3.14159];
        yield 'round: half away from zero' => [3.0, new RoundRule(), 2.5, ['precision' => 0]];
        yield 'round: negative half' => [-3.0, new RoundRule(), -2.5, ['precision' => 0]];
        yield 'round: ceil' => [3.0, new RoundRule(), 2.1, ['precision' => 0, 'mode' => 'ceil']];
        yield 'round: ceil of a negative' => [-2.0, new RoundRule(), -2.1, ['precision' => 0, 'mode' => 'ceil']];
        yield 'round: floor' => [2.0, new RoundRule(), 2.9, ['precision' => 0, 'mode' => 'floor']];
        yield 'round: negative precision' => [1200.0, new RoundRule(), 1234, ['precision' => -2]];
        yield 'round: numeric string' => [1.5, new RoundRule(), '1.499', ['precision' => 1]];
        yield 'round: not numeric' => ['x', new RoundRule(), 'x'];

        yield 'to_bool: yes' => [true, new ToBoolRule(), 'yes'];
        yield 'to_bool: TRUE with spaces' => [true, new ToBoolRule(), ' TRUE '];
        yield 'to_bool: on' => [true, new ToBoolRule(), 'on'];
        yield 'to_bool: one' => [true, new ToBoolRule(), '1'];
        yield 'to_bool: no' => [false, new ToBoolRule(), 'no'];
        yield 'to_bool: off' => [false, new ToBoolRule(), 'Off'];
        yield 'to_bool: false' => [false, new ToBoolRule(), 'false'];
        yield 'to_bool: zero' => [false, new ToBoolRule(), '0'];
        yield 'to_bool: empty' => [false, new ToBoolRule(), ''];
        yield 'to_bool: other text kept' => ['maybe', new ToBoolRule(), 'maybe'];
        yield 'to_bool: other number text kept' => ['2', new ToBoolRule(), '2'];
        yield 'to_bool: integer' => [true, new ToBoolRule(), 1];
        yield 'to_bool: zero integer' => [false, new ToBoolRule(), 0];
        yield 'to_bool: zero float' => [false, new ToBoolRule(), 0.0];
        yield 'to_bool: negative float' => [true, new ToBoolRule(), -1.5];
        yield 'to_bool: boolean kept' => [false, new ToBoolRule(), false];
        yield 'to_bool: null kept' => [null, new ToBoolRule(), null];
        yield 'to_string: integer' => ['42', new ToStringRule(), 42];
        yield 'to_string: float' => ['1.5', new ToStringRule(), 1.5];
        yield 'to_string: true' => ['1', new ToStringRule(), true];
        yield 'to_string: false' => ['', new ToStringRule(), false];
        yield 'to_string: Stringable' => ['text', new ToStringRule(), new class implements \Stringable {
            public function __toString(): string
            {
                return 'text';
            }
        }];
        yield 'to_string: null kept' => [null, new ToStringRule(), null];
        yield 'to_string: array kept' => [[1], new ToStringRule(), [1]];
        yield 'to_array: list kept' => [[1, 2], new ToArrayRule(), [1, 2]];
        yield 'to_array: map kept' => [['k' => 'v'], new ToArrayRule(), ['k' => 'v']];
        yield 'to_array: null' => [[], new ToArrayRule(), null];
        yield 'to_array: scalar wrapped' => [['a'], new ToArrayRule(), 'a'];
        yield 'to_array: zero wrapped' => [[0], new ToArrayRule(), 0];

        yield 'normalize_date: d/m/Y to ISO' => ['2026-11-28', new NormalizeDateRule(), '28/11/2026'];
        yield 'normalize_date: impossible date kept' => ['31/02/2026', new NormalizeDateRule(), '31/02/2026'];
        yield 'normalize_date: other format kept' => ['2026-11-28', new NormalizeDateRule(), '2026-11-28'];
        yield 'normalize_date: trailing data kept' => ['28/11/2026 ', new NormalizeDateRule(), '28/11/2026 '];
        yield 'normalize_date: formats given' => ['28/11/2026', new NormalizeDateRule(), '2026-11-28', ['from' => 'Y-m-d', 'to' => 'd/m/Y']];
        yield 'normalize_date: with time' => ['2026-11-28T14:30', new NormalizeDateRule(), '28/11/2026 14:30', ['from' => 'd/m/Y H:i', 'to' => 'Y-m-d\TH:i']];
        yield 'normalize_date: unnamed fields are zero' => ['2026-11-01 00:00', new NormalizeDateRule(), '11/2026', ['from' => 'm/Y', 'to' => 'Y-m-d H:i']];
        yield 'normalize_date: not a string' => [20261128, new NormalizeDateRule(), 20261128];
        yield 'timestamp_to_date: epoch' => ['1970-01-01 00:00:00', new TimestampToDateRule(), 0];
        yield 'timestamp_to_date: seconds' => ['2023-11-14 22:13:20', new TimestampToDateRule(), 1700000000];
        yield 'timestamp_to_date: numeric string' => ['2023-11-14 22:13:20', new TimestampToDateRule(), '1700000000'];
        yield 'timestamp_to_date: fraction not shown by default' => ['2023-11-14 22:13:20', new TimestampToDateRule(), 1700000000.9];
        yield 'timestamp_to_date: fraction kept as microseconds' => ['22:13:20.900000', new TimestampToDateRule(), 1700000000.9, ['format' => 'H:i:s.u']];
        yield 'timestamp_to_date: negative fraction' => ['23:59:58.500000', new TimestampToDateRule(), -1.5, ['format' => 'H:i:s.u']];
        yield 'timestamp_to_date: before the epoch' => ['1969-12-31 23:59:59', new TimestampToDateRule(), -1];
        yield 'timestamp_to_date: time zone' => ['2023-11-14 19:13', new TimestampToDateRule(), 1700000000, ['timezone' => 'America/Fortaleza', 'format' => 'Y-m-d H:i']];
        yield 'timestamp_to_date: first second of year 1' => ['0001-01-01 00:00:00', new TimestampToDateRule(), -62135596800];
        yield 'timestamp_to_date: before year 1' => [-62135596801, new TimestampToDateRule(), -62135596801];
        yield 'timestamp_to_date: last second of 9999' => ['9999-12-31 23:59:59', new TimestampToDateRule(), 253402300799];
        yield 'timestamp_to_date: after 9999' => [253402300800, new TimestampToDateRule(), 253402300800];
        yield 'timestamp_to_date: not numeric' => ['soon', new TimestampToDateRule(), 'soon'];

        yield 'digits_only: phone' => ['88999990000', new DigitsOnlyRule(), '(88) 99999-0000'];
        yield 'digits_only: ASCII digits only' => ['', new DigitsOnlyRule(), '١٢٣'];
        yield 'digits_only: not a string' => [12, new DigitsOnlyRule(), 12];
        yield 'alpha_only: letters of any script' => ['JoséΩ', new AlphaOnlyRule(), 'José Ω 123!'];
        yield 'alpha_only: invalid UTF-8' => ['ab', new AlphaOnlyRule(), "a\xffb"];
        yield 'alpha_only: not a string' => [1.5, new AlphaOnlyRule(), 1.5];
        yield 'alphanumeric_only: letters and numbers' => ['Ação5B٣', new AlphanumericOnlyRule(), 'Ação 5-B! ٣'];
        yield 'alphanumeric_only: invalid UTF-8' => ['a1', new AlphanumericOnlyRule(), "a\xff1"];
        yield 'alphanumeric_only: not a string' => [null, new AlphanumericOnlyRule(), null];
        yield 'email_filter: case and spaces' => ['ana.silva@example.com', new EmailFilterRule(), "  Ana.Silva@Example.COM \t\n"];
        yield 'email_filter: whitespace inside' => ['anasilva@x.com', new EmailFilterRule(), 'ana silva@x.com'];
        yield 'email_filter: characters an address cannot hold' => ['anawork@x.com', new EmailFilterRule(), 'ana (work)@x.com'];
        yield 'email_filter: non-ASCII dropped' => ['jos@x.com', new EmailFilterRule(), 'JOSÉ@x.com'];
        yield 'email_filter: not a string' => [5, new EmailFilterRule(), 5];

        yield 'format_cpf: digits' => ['123.456.789-09', new FormatCpfRule(), '12345678909'];
        yield 'format_cpf: already formatted' => ['123.456.789-09', new FormatCpfRule(), '123.456.789-09'];
        yield 'format_cpf: other punctuation' => ['123.456.789-09', new FormatCpfRule(), '123 456 789/09'];
        yield 'format_cpf: ten digits kept' => ['1234567890', new FormatCpfRule(), '1234567890'];
        yield 'format_cpf: integer kept' => [12345678909, new FormatCpfRule(), 12345678909];
        yield 'format_cnpj: digits' => ['11.222.333/0001-81', new FormatCnpjRule(), '11222333000181'];
        yield 'format_cnpj: alphanumeric, lower case' => ['12.ABC.345/01DE-35', new FormatCnpjRule(), '12abc34501de35'];
        yield 'format_cnpj: already formatted' => ['12.ABC.345/01DE-35', new FormatCnpjRule(), '12.ABC.345/01DE-35'];
        yield 'format_cnpj: letters in the check digits kept' => ['12ABC34501DEAB', new FormatCnpjRule(), '12ABC34501DEAB'];
        yield 'format_cnpj: thirteen characters kept' => ['1122233300018', new FormatCnpjRule(), '1122233300018'];
        yield 'format_cnpj: not a string' => [false, new FormatCnpjRule(), false];
        yield 'format_cep: digits' => ['63100-000', new FormatCepRule(), '63100000'];
        yield 'format_cep: other punctuation' => ['63100-000', new FormatCepRule(), '63.100 000'];
        yield 'format_cep: seven digits kept' => ['6310000', new FormatCepRule(), '6310000'];
        yield 'format_cep: integer kept' => [63100000, new FormatCepRule(), 63100000];
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
    public function infinityAndNotANumberAreNotNumbers(): void
    {
        self::assertSame(INF, (new ToFloatRule())->sanitize(INF, self::context()));
        self::assertSame(-INF, (new ClampRule())->sanitize(-INF, self::context(['min' => 0])));
        $rounded = (new RoundRule())->sanitize(NAN, self::context());
        self::assertIsFloat($rounded);
        self::assertNan($rounded);
        self::assertSame(INF, (new ToBoolRule())->sanitize(INF, self::context()));
        self::assertSame(INF, (new TimestampToDateRule())->sanitize(INF, self::context()));
    }

    #[Test]
    public function rulesNameThemselves(): void
    {
        $names = array_map(static fn(SanitizationRule $rule): string => $rule->name(), [
            new ToIntRule(), new ToFloatRule(), new ClampRule(), new RoundRule(), new ToBoolRule(), new ToStringRule(), new ToArrayRule(),
            new NormalizeDateRule(), new TimestampToDateRule(), new DigitsOnlyRule(), new AlphaOnlyRule(), new AlphanumericOnlyRule(),
            new EmailFilterRule(), new FormatCpfRule(), new FormatCnpjRule(), new FormatCepRule(),
        ]);

        self::assertSame([
            'numeric.to_int', 'numeric.to_float', 'numeric.clamp', 'numeric.round', 'type.to_bool', 'type.to_string', 'type.to_array',
            'date.normalize', 'date.timestamp_to_date', 'filter.digits_only', 'filter.alpha_only', 'filter.alphanumeric_only',
            'filter.email', 'brazilian.format_cpf', 'brazilian.format_cnpj', 'brazilian.format_cep',
        ], $names);
    }

    /**
     * @return iterable<string, array{SanitizerCode, SanitizationRule, array<string, mixed>, non-empty-string}>
     */
    public static function misconfigurations(): iterable
    {
        $invalid = SanitizerCode::InvalidRuleParameter;
        $missing = SanitizerCode::MissingRuleParameter;
        yield 'clamp without bounds' => [$missing, new ClampRule(), [], 'needs the parameter "min", "max" or both.'];
        yield 'clamp with min above max' => [$invalid, new ClampRule(), ['min' => 10, 'max' => 1], 'must be at most "max".'];
        yield 'clamp with a text bound' => [$invalid, new ClampRule(), ['min' => '1'], 'must be a finite number, string given.'];
        yield 'clamp with an infinite bound' => [$invalid, new ClampRule(), ['max' => INF], 'must be a finite number, float given.'];
        yield 'clamp with a null bound' => [$invalid, new ClampRule(), ['min' => null], 'must be a finite number, null given.'];
        yield 'round precision not an integer' => [$invalid, new RoundRule(), ['precision' => 1.0], 'must be an integer, float given.'];
        yield 'round mode that is not one' => [$invalid, new RoundRule(), ['mode' => 'up'], 'must be round, ceil or floor.'];
        yield 'normalize_date from not a string' => [$invalid, new NormalizeDateRule(), ['from' => ['d/m/Y']], 'must be a string, array given.'];
        yield 'normalize_date to not a string' => [$invalid, new NormalizeDateRule(), ['to' => false], 'must be a string, bool given.'];
        yield 'timestamp_to_date with an empty time zone' => [$invalid, new TimestampToDateRule(), ['timezone' => ''], 'must be a time zone identifier.'];
        yield 'timestamp_to_date with an unknown time zone' => [$invalid, new TimestampToDateRule(), ['timezone' => 'Mars/Olympus'], "must be a time zone identifier, 'Mars/Olympus' given."];
        yield 'timestamp_to_date format not a string' => [$invalid, new TimestampToDateRule(), ['format' => 1], 'must be a string, int given.'];
    }

    /**
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
        $rule->sanitize(null, self::context($parameters));
    }
}
