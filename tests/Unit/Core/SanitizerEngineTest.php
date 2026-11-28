<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Tests\Unit\Core;

use ArrayObject;
use KaririCode\Sanitizer\Configuration\SanitizerConfiguration;
use KaririCode\Sanitizer\Contract\SanitizationContext;
use KaririCode\Sanitizer\Contract\SanitizationRule;
use KaririCode\Sanitizer\Core\InMemoryRuleRegistry;
use KaririCode\Sanitizer\Core\SanitizerEngine;
use KaririCode\Sanitizer\Event\SanitizationCompletedEvent;
use KaririCode\Sanitizer\Event\SanitizationStartedEvent;
use KaririCode\Sanitizer\Exception\InvalidRuleException;
use KaririCode\Sanitizer\Exception\SanitizerCode;
use KaririCode\Sanitizer\Result\FieldModification;
use KaririCode\Sanitizer\Rule\Numeric\ClampRule;
use KaririCode\Sanitizer\Rule\String\TrimRule;
use KaririCode\Sanitizer\Rule\String\UpperCaseRule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

// ── Unit: SanitizerEngineTest ────────────────────────────────────────
// Rule maps, definitions, chaining, absent and nested fields, events.
// ─────────────────────────────────────────────────────────────────────

#[CoversClass(SanitizerEngine::class)]
#[CoversClass(InMemoryRuleRegistry::class)]
#[CoversClass(SanitizationStartedEvent::class)]
#[CoversClass(SanitizationCompletedEvent::class)]
#[CoversClass(SanitizerConfiguration::class)]
final class SanitizerEngineTest extends TestCase
{
    #[Test]
    public function rulesRunInOrderEachOnTheOutputOfThePrevious(): void
    {
        $result = (new SanitizerEngine())->sanitize(
            ['email' => '  ANA@Example.COM ', 'name' => 'ana', 'age' => '42'],
            ['email' => ['trim', 'lower_case'], 'name' => ['trim', 'capitalize'], 'age' => ['to_int']],
        );

        self::assertSame(['email' => 'ana@example.com', 'name' => 'Ana', 'age' => 42], $result->sanitized);
        self::assertSame(
            [
                ['email', 'string.trim', '  ANA@Example.COM ', 'ANA@Example.COM'],
                ['email', 'string.lower_case', 'ANA@Example.COM', 'ana@example.com'],
                ['name', 'string.capitalize', 'ana', 'Ana'],
                ['age', 'numeric.to_int', '42', 42],
            ],
            array_map(static fn(FieldModification $change): array => [$change->field, $change->ruleName, $change->before, $change->after], $result->modifications),
        );
    }

    #[Test]
    public function definitionsMayBeAliasesRulesOrPairs(): void
    {
        $result = (new SanitizerEngine())->sanitize(
            ['a' => ' a ', 'b' => ' b ', 'c' => 'xxc', 'd' => 'd', 'e' => ' e ', 'f' => 150],
            [
                'a' => ['trim'],
                'b' => [new TrimRule()],
                'c' => [['trim', ['characters' => 'x']]],
                'd' => [[new UpperCaseRule()]],
                'e' => [['trim']],
                'f' => [[new ClampRule(), ['max' => 100]]],
            ],
        );

        self::assertSame(['a' => 'a', 'b' => 'b', 'c' => 'c', 'd' => 'D', 'e' => 'e', 'f' => 100], $result->sanitized);
    }

    /**
     * @return iterable<string, array{mixed, string}>
     */
    public static function invalidDefinitions(): iterable
    {
        yield 'rules not a list' => ['trim', 'the rules of a field are a list'];
        yield 'rules as a map' => [['first' => 'trim'], 'the rules of a field are a list'];
        yield 'a number' => [[42], 'a rule is an alias, a SanitizationRule, or [alias or rule, parameters]'];
        yield 'an empty pair' => [[[]], 'the first item names the rule, by alias or as a SanitizationRule'];
        yield 'three items' => [[['truncate', ['max' => 5], 'extra']], 'a rule is an alias, a SanitizationRule, or [alias or rule, parameters]'];
        yield 'a map' => [[['rule' => 'trim']], 'a rule is an alias, a SanitizationRule, or [alias or rule, parameters]'];
        yield 'a rule that is a number' => [[[42, []]], 'the first item names the rule, by alias or as a SanitizationRule'];
        yield 'parameters not an array' => [[['truncate', 5]], 'the parameters of a rule are a map of names to values'];
        yield 'parameters as a list' => [[['truncate', [5, '...']]], 'the parameters of a rule are a map of names to values'];
        yield 'parameters partly a list' => [[['truncate', ['max' => 5, '...']]], 'the parameters of a rule are a map of names to values'];
    }

    #[Test]
    #[DataProvider('invalidDefinitions')]
    public function malformedDefinitionsAreRefused(mixed $rules, string $reason): void
    {
        try {
            (new SanitizerEngine())->sanitize(['name' => 'x'], ['name' => $rules]);
            self::fail('A malformed definition was accepted.');
        } catch (InvalidRuleException $exception) {
            self::assertSame(SanitizerCode::InvalidRuleDefinition, $exception->errorCode);
            self::assertSame('[SAN_3303] Invalid rule definition for field "name": ' . $reason . '.', $exception->getMessage());
        }
    }

    #[Test]
    public function definitionsAreCheckedEvenForFieldsTheDataLacks(): void
    {
        $engine = new SanitizerEngine();

        try {
            $engine->sanitize([], ['nick' => ['trimm']]);
            self::fail('An unknown alias was accepted.');
        } catch (InvalidRuleException $exception) {
            self::assertSame('[SAN_3302] No rule is registered under the alias "trimm".', $exception->getMessage());
        }

        $this->expectExceptionObject(InvalidRuleException::invalidDefinition(field: 'nick', reason: 'the rules of a field are a list'));
        $engine->sanitize([], ['nick' => 'trim']);
    }

    #[Test]
    public function fieldsTheDataLacksAreNotAdded(): void
    {
        $result = (new SanitizerEngine())->sanitize(
            ['name' => ' Ana ', 'nick' => null],
            ['name' => ['trim'], 'email' => ['trim', 'lower_case'], 'address.city' => ['trim'], 'nick' => ['trim', 'to_array']],
        );

        self::assertSame(['name' => 'Ana', 'nick' => []], $result->sanitized);
        self::assertSame(['name', 'nick'], $result->modifiedFields());
    }

    #[Test]
    public function nestedFieldsAreWrittenWhereTheyWereRead(): void
    {
        $data = ['user' => ['email' => ' A@X.COM ', 'name' => 'x'], 'tags' => [' a ', ' b '], 'a.b' => ' literal ', 'a' => ['b' => ' nested '], 7 => ' seven '];

        $result = (new SanitizerEngine())->sanitize($data, ['user.email' => ['trim', 'lower_case'], 'tags.1' => ['trim'], 'a.b' => ['trim'], 7 => ['trim']]);

        self::assertSame(['user' => ['email' => 'a@x.com', 'name' => 'x'], 'tags' => [' a ', 'b'], 'a.b' => 'literal', 'a' => ['b' => ' nested '], 7 => 'seven'], $result->sanitized);
        self::assertSame($data, $result->original);
        self::assertSame('a@x.com', $result->get('user.email'));
    }

    #[Test]
    public function rulesSeeTheFieldTheirParametersAndTheDataAsGiven(): void
    {
        /** @var ArrayObject<int, array{mixed, string, array<string, mixed>, array<array-key, mixed>}> $seen */
        $seen = new ArrayObject();
        $spy = new readonly class ($seen) implements SanitizationRule {
            /** @param ArrayObject<int, array{mixed, string, array<string, mixed>, array<array-key, mixed>}> $seen */
            public function __construct(private ArrayObject $seen) {}

            public function sanitize(mixed $value, SanitizationContext $context): mixed
            {
                $this->seen->append([$value, $context->field, $context->parameters, $context->data]);

                return is_string($value) ? strtoupper($value) : $value;
            }

            public function name(): string
            {
                return 'test.spy';
            }
        };
        $data = ['address' => ['city' => 'crato'], 'state' => 'ce'];

        (new SanitizerEngine())->sanitize($data, ['address.city' => [[$spy, ['mode' => 1, 'strict' => false]]], 'state' => [$spy, [$spy, ['again' => true]]]]);

        self::assertSame([
            ['crato', 'address.city', ['mode' => 1, 'strict' => false], $data],
            ['ce', 'state', [], $data],
            ['CE', 'state', ['again' => true], $data],
        ], $seen->getArrayCopy());
    }

    #[Test]
    public function aMisconfiguredRuleNamesTheField(): void
    {
        $this->expectExceptionObject(InvalidRuleException::missingParameter(field: 'code', parameter: 'length'));

        (new SanitizerEngine())->sanitize(['code' => '7'], ['code' => ['pad']]);
    }

    #[Test]
    public function trackingCanBeTurnedOff(): void
    {
        $configuration = new SanitizerConfiguration(trackModifications: false);
        $engine = new SanitizerEngine(configuration: $configuration);
        $result = $engine->sanitize(['a' => ' x '], ['a' => ['trim', 'upper_case']]);

        self::assertSame($configuration, $engine->configuration);
        self::assertSame(['a' => 'X'], $result->sanitized);
        self::assertSame([], $result->modifications);
        self::assertTrue($result->wasModified());
        self::assertTrue($result->isFieldModified('a'));
        self::assertTrue((new SanitizerConfiguration())->trackModifications);
    }

    #[Test]
    public function rulesThatChangeNothingRecordNothing(): void
    {
        $result = (new SanitizerEngine())->sanitize(['a' => 'x', 'b' => 1], ['a' => ['trim', 'lower_case'], 'b' => ['to_int']]);

        self::assertSame([], $result->modifications);
        self::assertFalse($result->wasModified());
    }

    #[Test]
    public function theEngineUsesTheRegistryItIsGiven(): void
    {
        $registry = new InMemoryRuleRegistry();
        $registry->register('shout', new UpperCaseRule());
        $engine = new SanitizerEngine($registry);

        self::assertSame($registry, $engine->registry);
        self::assertSame(['a' => 'X'], $engine->sanitize(['a' => 'x'], ['a' => ['shout']])->sanitized);
        $this->expectExceptionObject(InvalidRuleException::unknownAlias('trim'));
        $engine->sanitize(['a' => 'x'], ['a' => ['trim']]);
    }

    #[Test]
    public function theObserverSeesTheStartAndTheEnd(): void
    {
        /** @var ArrayObject<int, object> $events */
        $events = new ArrayObject();
        $engine = new SanitizerEngine(observer: static function (object $event) use ($events): void {
            $events->append($event);
        });

        $result = $engine->sanitize(['a' => ' x '], ['a' => ['trim'], 3 => ['trim']]);

        self::assertCount(2, $events);
        $started = $events[0];
        $completed = $events[1];
        self::assertInstanceOf(SanitizationStartedEvent::class, $started);
        self::assertInstanceOf(SanitizationCompletedEvent::class, $completed);
        self::assertSame(['a', '3'], $started->fields);
        self::assertEqualsWithDelta(microtime(true), $started->timestamp, 5.0);
        self::assertSame($result, $completed->result);
        self::assertGreaterThan(0.0, $completed->durationMs);
        self::assertLessThan(5000.0, $completed->durationMs);
    }

    #[Test]
    public function theRegistryKeepsRulesByAlias(): void
    {
        $registry = new InMemoryRuleRegistry();
        $rule = new TrimRule();
        $registry->register('strip', $rule);

        self::assertSame($rule, $registry->resolve('strip'));
        self::assertTrue($registry->has('strip'));
        self::assertFalse($registry->has('trim'));
        self::assertSame(['strip'], $registry->aliases());

        foreach ([
            [static fn() => $registry->register('strip', $rule), SanitizerCode::DuplicateRuleAlias, 'A rule is already registered under the alias "strip".'],
            [static fn() => $registry->register('', $rule), SanitizerCode::InvalidRuleDefinition, 'A rule alias must not be empty.'],
            [static fn() => $registry->resolve('trim'), SanitizerCode::UnknownRuleAlias, 'No rule is registered under the alias "trim".'],
        ] as [$call, $code, $message]) {
            try {
                $call();
                self::fail($message);
            } catch (InvalidRuleException $exception) {
                self::assertSame($code, $exception->errorCode);
                self::assertStringEndsWith($message, $exception->getMessage());
            }
        }
    }

    #[Test]
    public function theDefaultRegistryHoldsTheThirtyThreeRules(): void
    {
        $registry = (new SanitizerEngine())->registry;
        $aliases = $registry->aliases();

        self::assertCount(33, $aliases);
        self::assertSame(['trim', 'lower_case', 'upper_case', 'capitalize', 'slug'], array_slice($aliases, 0, 5));
        self::assertSame(['format_cpf', 'format_cnpj', 'format_cep'], array_slice($aliases, -3));
        self::assertSame('html.purify', $registry->resolve('html_purify')->name());
        self::assertSame('filter.email', $registry->resolve('email_filter')->name());
        self::assertCount(count($aliases), array_unique(array_map(static fn(string $alias): string => $registry->resolve($alias)->name(), $aliases)));
    }
}
