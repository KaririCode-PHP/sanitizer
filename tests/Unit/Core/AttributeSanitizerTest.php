<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Tests\Unit\Core;

use KaririCode\Sanitizer\Attribute\Sanitize;
use KaririCode\Sanitizer\Core\AttributeRules;
use KaririCode\Sanitizer\Core\AttributeSanitizer;
use KaririCode\Sanitizer\Core\SanitizerEngine;
use KaririCode\Sanitizer\Exception\InvalidRuleException;
use KaririCode\Sanitizer\Exception\SanitizerCode;
use KaririCode\Sanitizer\Result\SanitizedObject;
use KaririCode\Sanitizer\Rule\String\TrimRule;
use KaririCode\Sanitizer\Tests\Support\Profile;
use KaririCode\Sanitizer\Tests\Support\ReflectionApplier;
use KaririCode\Sanitizer\Tests\Support\ReflectionInspector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

// ── Unit: AttributeSanitizerTest ─────────────────────────────────────
// #[Sanitize] through the contract PropertyInspector and PropertyChangeApplier.
// ─────────────────────────────────────────────────────────────────────

#[CoversClass(AttributeSanitizer::class)]
#[CoversClass(AttributeRules::class)]
#[CoversClass(Sanitize::class)]
#[CoversClass(SanitizedObject::class)]
final class AttributeSanitizerTest extends TestCase
{
    #[Test]
    public function aReadonlyObjectComesBackAsASanitizedCopy(): void
    {
        $inspector = new ReflectionInspector();
        $applier = new ReflectionApplier();
        $profile = new Profile(
            email: '  ANA@Example.COM ',
            name: "  ana   da\tsilva ",
            bio: '<p>Hello &lt;b&gt;world&lt;/b&gt;, this is a long bio</p><script>x()</script>',
            cnpj: '12abc34501de35',
        );

        $sanitized = (new AttributeSanitizer(new SanitizerEngine(), $inspector, $applier))->sanitize($profile);
        $copy = $sanitized->object;

        self::assertInstanceOf(Profile::class, $copy);
        self::assertNotSame($profile, $copy);
        self::assertSame(
            ['ana@example.com', 'Ana Da Silva', 'Hello world, this i…', '12.ABC.345/01DE-35', '  kept  '],
            [$copy->email, $copy->name, $copy->bio, $copy->cnpj, $copy->notSanitized],
        );
        self::assertSame('  ANA@Example.COM ', $profile->email);
        self::assertSame(['email', 'name', 'bio', 'cnpj'], $sanitized->result->modifiedFields());
        self::assertSame([['email' => 'ana@example.com', 'name' => 'Ana Da Silva', 'bio' => 'Hello world, this i…', 'cnpj' => '12.ABC.345/01DE-35']], $applier->applied);
        self::assertSame([Sanitize::class], $inspector->inspected);
    }

    #[Test]
    public function onlyThePropertiesThatChangedAreApplied(): void
    {
        $applier = new ReflectionApplier();
        $sanitizer = new AttributeSanitizer(new SanitizerEngine(), new ReflectionInspector(), $applier);

        $sanitized = $sanitizer->sanitize(new Profile(email: 'ana@example.com', name: 'ana'));

        self::assertSame([['name' => 'Ana']], $applier->applied);
        self::assertSame(['email' => 'ana@example.com', 'name' => 'Ana', 'bio' => '', 'cnpj' => null], $sanitized->result->sanitized);
    }

    #[Test]
    public function anObjectThatNeedsNothingIsReturnedAsItIs(): void
    {
        $applier = new ReflectionApplier();
        $profile = new Profile(email: 'ana@example.com', name: 'Ana', cnpj: '11.222.333/0001-81');

        $sanitized = (new AttributeSanitizer(new SanitizerEngine(), new ReflectionInspector(), $applier))->sanitize($profile);

        self::assertSame($profile, $sanitized->object);
        self::assertSame([], $applier->applied);
        self::assertFalse($sanitized->result->wasModified());
    }

    #[Test]
    public function anObjectWithoutAttributesIsReturnedAsItIs(): void
    {
        $object = new stdClass();
        $object->name = ' x ';

        $sanitized = (new AttributeSanitizer(new SanitizerEngine(), new ReflectionInspector(), new ReflectionApplier()))->sanitize($object);

        self::assertSame($object, $sanitized->object);
        self::assertSame([], $sanitized->result->sanitized);
    }

    #[Test]
    public function theCollectorIgnoresOtherAttributesAndJoinsRepeatedOnes(): void
    {
        $trim = new TrimRule();
        $rules = new AttributeRules();
        $rules->handleAttribute('a', new Sanitize('normalize_whitespace'), ' x ');
        $rules->handleAttribute('a', new Sanitize($trim, ['truncate', ['max' => 3]]), 'ignored: the first value stands');
        $rules->handleAttribute('a', new stdClass(), 'ignored');
        $rules->handleAttribute('b', new stdClass(), 'not collected');
        $rules->handleAttribute('c', new Sanitize('lower_case'), 'Y');

        self::assertSame(['a' => ['normalize_whitespace', $trim, ['truncate', ['max' => 3]]], 'c' => ['lower_case']], $rules->fieldRules());
        self::assertSame(['a' => ' x ', 'c' => 'Y'], $rules->values());
    }

    #[Test]
    public function sanitizeTakesPositionalRulesOnly(): void
    {
        self::assertSame(['trim', ['truncate', ['max' => 3]]], (new Sanitize('trim', ['truncate', ['max' => 3]]))->rules);
        self::assertSame([], (new Sanitize())->rules);

        try {
            new Sanitize('trim', mode: 'strict', level: 'high');
            self::fail('Named arguments were accepted.');
        } catch (InvalidRuleException $exception) {
            self::assertSame(SanitizerCode::NamedAttributeArguments, $exception->errorCode);
            self::assertSame("[SAN_3306] #[Sanitize] takes no named arguments (mode, level); pass parameters as ['alias', ['parameter' => value]].", $exception->getMessage());
        }
    }
}
