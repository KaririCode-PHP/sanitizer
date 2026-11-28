<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Provider;

use Closure;
use KaririCode\Contract\DI\BindableContainer;
use KaririCode\Contract\DI\ServiceProvider;
use KaririCode\Contract\Inspection\PropertyChangeApplier;
use KaririCode\Contract\Inspection\PropertyInspector;
use KaririCode\Sanitizer\Configuration\SanitizerConfiguration;
use KaririCode\Sanitizer\Contract\RuleRegistry;
use KaririCode\Sanitizer\Core\AttributeSanitizer;
use KaririCode\Sanitizer\Core\InMemoryRuleRegistry;
use KaririCode\Sanitizer\Core\SanitizerEngine;
use KaririCode\Sanitizer\Exception\ConfigurationException;

// ── Provider: SanitizerServiceProvider ───────────────────────────────
// Registers the sanitizer in a kariricode/contract container.
// ─────────────────────────────────────────────────────────────────────

/**
 * Binds, as singletons, the rule registry with the built-in rules, the
 * engine and the AttributeSanitizer. The AttributeSanitizer takes the
 * PropertyInspector and the PropertyChangeApplier the container holds,
 * so bind them (kariricode/property-inspector's implementations) before
 * resolving it.
 *
 * Preconditions: services are resolved after register(); a binding that
 * resolves to the wrong type throws ConfigurationException.
 *
 * Thread-safety: immutable; the container owns the instances.
 *
 * Side effects: register() adds bindings to the container.
 *
 * Example:
 *
 *     (new SanitizerServiceProvider())->register($container);
 *     $engine = $container->get(SanitizerEngine::class);
 *
 * References: kariricode/contract DI\ServiceProvider; ADR-006.
 */
final readonly class SanitizerServiceProvider implements ServiceProvider
{
    public function __construct(
        private SanitizerConfiguration $configuration = new SanitizerConfiguration(),
        private ?Closure $observer = null,
    ) {}

    #[\Override]
    public function register(BindableContainer $container): void
    {
        $container->bind(abstract: RuleRegistry::class, concrete: static fn(): RuleRegistry => InMemoryRuleRegistry::withDefaults(), singleton: true);
        $container->bind(abstract: SanitizerEngine::class, concrete: fn(): SanitizerEngine => new SanitizerEngine(
            registry: self::service($container, RuleRegistry::class),
            configuration: $this->configuration,
            observer: $this->observer,
        ), singleton: true);
        $container->bind(abstract: AttributeSanitizer::class, concrete: static fn(): AttributeSanitizer => new AttributeSanitizer(
            engine: self::service($container, SanitizerEngine::class),
            inspector: self::service($container, PropertyInspector::class),
            applier: self::service($container, PropertyChangeApplier::class),
        ), singleton: true);
    }

    #[\Override]
    public function boot(): void {}

    /**
     * @template T of object
     *
     * @param class-string<T> $id
     *
     * @return T
     */
    private static function service(BindableContainer $container, string $id): object
    {
        /** @var mixed $service */
        $service = $container->get($id);

        return $service instanceof $id ? $service : throw ConfigurationException::invalidBinding(id: $id, type: get_debug_type($service));
    }
}
