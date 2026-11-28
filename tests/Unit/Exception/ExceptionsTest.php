<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Tests\Unit\Exception;

use Closure;
use KaririCode\Contract\Exception\Configuration;
use KaririCode\Contract\Exception\Validation;
use KaririCode\Sanitizer\Exception\ConfigurationException;
use KaririCode\Sanitizer\Exception\InvalidRuleException;
use KaririCode\Sanitizer\Exception\SanitizerCode;
use KaririCode\Sanitizer\Exception\SanitizerException;
use KaririCode\Sanitizer\Exception\UnsupportedInputException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Throwable;

// ── Unit: ExceptionsTest ─────────────────────────────────────────────
// Each named constructor: its code, its family, its message.
// ─────────────────────────────────────────────────────────────────────

#[CoversClass(SanitizerCode::class)]
#[CoversClass(InvalidRuleException::class)]
#[CoversClass(ConfigurationException::class)]
#[CoversClass(UnsupportedInputException::class)]
final class ExceptionsTest extends TestCase
{
    /**
     * @return iterable<string, array{Closure(): Throwable, SanitizerCode, class-string, string}>
     */
    public static function exceptions(): iterable
    {
        yield 'duplicate alias' => [static fn(): Throwable => InvalidRuleException::duplicateAlias('trim'), SanitizerCode::DuplicateRuleAlias, Configuration::class, 'A rule is already registered under the alias "trim".'];
        yield 'unknown alias' => [static fn(): Throwable => InvalidRuleException::unknownAlias("tr\nim"), SanitizerCode::UnknownRuleAlias, Configuration::class, 'No rule is registered under the alias "tr\\nim".'];
        yield 'invalid definition' => [static fn(): Throwable => InvalidRuleException::invalidDefinition('name', 'a reason'), SanitizerCode::InvalidRuleDefinition, Configuration::class, 'Invalid rule definition for field "name": a reason.'];
        yield 'empty alias' => [static fn(): Throwable => InvalidRuleException::emptyAlias(), SanitizerCode::InvalidRuleDefinition, Configuration::class, 'A rule alias must not be empty.'];
        yield 'missing parameter' => [static fn(): Throwable => InvalidRuleException::missingParameter('code', 'length'), SanitizerCode::MissingRuleParameter, Configuration::class, 'A rule on field "code" needs the parameter "length".'];
        yield 'missing bounds' => [static fn(): Throwable => InvalidRuleException::missingBounds('age'), SanitizerCode::MissingRuleParameter, Configuration::class, 'A rule on field "age" needs the parameter "min", "max" or both.'];
        yield 'invalid parameter' => [static fn(): Throwable => InvalidRuleException::invalidParameter(field: 'age', parameter: 'min', expected: 'a finite number', given: 'string'), SanitizerCode::InvalidRuleParameter, Configuration::class, 'The parameter "min" of a rule on field "age" must be a finite number, string given.'];
        yield 'invalid parameter, nothing given' => [static fn(): Throwable => InvalidRuleException::invalidParameter(field: 'code', parameter: 'side', expected: 'left, right or both'), SanitizerCode::InvalidRuleParameter, Configuration::class, 'The parameter "side" of a rule on field "code" must be left, right or both.'];
        yield 'named arguments' => [static fn(): Throwable => InvalidRuleException::namedArguments('Sanitize', ['max']), SanitizerCode::NamedAttributeArguments, Configuration::class, "#[Sanitize] takes no named arguments (max); pass parameters as ['alias', ['parameter' => value]]."];
        yield 'invalid binding' => [static fn(): Throwable => ConfigurationException::invalidBinding('App\\Service', 'string'), SanitizerCode::InvalidBinding, Configuration::class, 'The container resolves "App\\\\Service" to string, which is not an instance of it.'];
        yield 'unsupported input' => [static fn(): Throwable => UnsupportedInputException::notAnArray('string'), SanitizerCode::UnsupportedInput, Validation::class, 'The sanitization processor expects an array of fields, string given.'];
    }

    /**
     * @param Closure(): Throwable $build
     * @param class-string         $family
     */
    #[Test]
    #[DataProvider('exceptions')]
    public function eachExceptionCarriesItsCodeFamilyAndMessage(Closure $build, SanitizerCode $code, string $family, string $message): void
    {
        $exception = $build();
        self::assertInstanceOf(SanitizerException::class, $exception);
        self::assertInstanceOf($family, $exception);
        self::assertSame($code, $exception->errorCode);
        self::assertSame($code->value, $exception->getCode());
        self::assertSame("[SAN_{$code->value}] {$message}", $exception->getMessage());
    }

    #[Test]
    public function theCodeTableNamesItsPrefixAndBlock(): void
    {
        self::assertSame('SAN', SanitizerCode::prefix());
        self::assertSame([3300, 3399], [SanitizerCode::block()->first, SanitizerCode::block()->last]);
    }
}
