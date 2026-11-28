<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Tests\Unit\Integration;

use ArrayObject;
use KaririCode\Contract\Inspection\PropertyChangeApplier;
use KaririCode\Contract\Inspection\PropertyInspector;
use KaririCode\Sanitizer\Configuration\SanitizerConfiguration;
use KaririCode\Sanitizer\Contract\RuleRegistry;
use KaririCode\Sanitizer\Core\AttributeSanitizer;
use KaririCode\Sanitizer\Core\SanitizerEngine;
use KaririCode\Sanitizer\Exception\ConfigurationException;
use KaririCode\Sanitizer\Exception\SanitizerCode;
use KaririCode\Sanitizer\Exception\UnsupportedInputException;
use KaririCode\Sanitizer\Integration\SanitizationProcessor;
use KaririCode\Sanitizer\Provider\SanitizerServiceProvider;
use KaririCode\Sanitizer\Tests\Support\MemoryContainer;
use KaririCode\Sanitizer\Tests\Support\Profile;
use KaririCode\Sanitizer\Tests\Support\ReflectionApplier;
use KaririCode\Sanitizer\Tests\Support\ReflectionInspector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

// ── Unit: IntegrationTest ────────────────────────────────────────────
// The contract Processor and Sanitizer, and the service provider.
// ─────────────────────────────────────────────────────────────────────

#[CoversClass(SanitizationProcessor::class)]
#[CoversClass(SanitizerServiceProvider::class)]
final class IntegrationTest extends TestCase
{
    #[Test]
    public function theProcessorReturnsTheSanitizedFields(): void
    {
        $processor = new SanitizationProcessor(new SanitizerEngine(), ['email' => ['trim', 'lower_case'], 'phone' => ['digits_only']]);
        $input = ['email' => ' ANA@X.COM ', 'extra' => ' kept '];

        self::assertSame(['email' => 'ana@x.com', 'extra' => ' kept '], $processor->process($input));
        self::assertSame(['email' => 'ana@x.com', 'extra' => ' kept '], $processor->sanitize($input));
    }

    #[Test]
    public function theProcessorSanitizesArraysOnly(): void
    {
        $processor = new SanitizationProcessor(new SanitizerEngine(), ['email' => ['trim']]);

        foreach ([' ana@x.com ', new stdClass(), null] as $input) {
            try {
                $processor->process($input);
                self::fail('A non-array was sanitized.');
            } catch (UnsupportedInputException $exception) {
                self::assertSame(SanitizerCode::UnsupportedInput, $exception->errorCode);
                self::assertStringEndsWith(get_debug_type($input) . ' given.', $exception->getMessage());
            }
        }
    }

    #[Test]
    public function theProviderBindsEverySingleton(): void
    {
        $container = new MemoryContainer();
        $configuration = new SanitizerConfiguration(trackModifications: false);
        $events = new ArrayObject();
        (new SanitizerServiceProvider($configuration, static function (object $event) use ($events): void {
            $events->append($event::class);
        }))->register($container);
        $container->instance(PropertyInspector::class, new ReflectionInspector());
        $container->instance(PropertyChangeApplier::class, new ReflectionApplier());

        self::assertSame([RuleRegistry::class, SanitizerEngine::class, AttributeSanitizer::class], $container->singletons);
        $engine = $container->get(SanitizerEngine::class);
        self::assertInstanceOf(SanitizerEngine::class, $engine);
        self::assertSame($configuration, $engine->configuration);
        self::assertSame($container->get(RuleRegistry::class), $engine->registry);
        self::assertSame([], $engine->sanitize(['a' => ' x '], ['a' => ['trim']])->modifications);
        self::assertCount(2, $events);

        $sanitizer = $container->get(AttributeSanitizer::class);
        self::assertInstanceOf(AttributeSanitizer::class, $sanitizer);
        $copy = $sanitizer->sanitize(new Profile(email: ' ANA@X.COM ', name: 'ana'))->object;
        self::assertInstanceOf(Profile::class, $copy);
        self::assertSame(['ana@x.com', 'Ana'], [$copy->email, $copy->name]);
        self::assertSame($sanitizer, $container->get(AttributeSanitizer::class));
        (new SanitizerServiceProvider())->boot();
    }

    #[Test]
    public function aBindingOfTheWrongTypeIsReported(): void
    {
        $container = new MemoryContainer();
        (new SanitizerServiceProvider())->register($container);
        $container->instance(RuleRegistry::class, new stdClass());

        $this->expectExceptionObject(ConfigurationException::invalidBinding(id: RuleRegistry::class, type: 'stdClass'));
        $this->expectExceptionCode(SanitizerCode::InvalidBinding->value);

        $container->get(SanitizerEngine::class);
    }

    #[Test]
    public function theAttributeSanitizerNeedsBothInspectionServices(): void
    {
        $container = new MemoryContainer();
        (new SanitizerServiceProvider())->register($container);
        $container->instance(PropertyInspector::class, new ReflectionInspector());
        $container->instance(PropertyChangeApplier::class, 'not an applier');

        $this->expectExceptionObject(ConfigurationException::invalidBinding(id: PropertyChangeApplier::class, type: 'string'));

        $container->get(AttributeSanitizer::class);
    }
}
