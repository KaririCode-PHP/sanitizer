<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Core;

use KaririCode\Contract\Inspection\PropertyChangeApplier;
use KaririCode\Contract\Inspection\PropertyInspector;
use KaririCode\Sanitizer\Attribute\Sanitize;
use KaririCode\Sanitizer\Result\SanitizedObject;

// ── Core: AttributeSanitizer ─────────────────────────────────────────
// Sanitizes an object by the #[Sanitize] attributes on its properties.
// ─────────────────────────────────────────────────────────────────────

/**
 * Reads an object's #[Sanitize] attributes through a kariricode/contract
 * PropertyInspector, sanitizes the property values with the engine, and
 * writes the changed values to a copy through a PropertyChangeApplier,
 * so readonly DTOs work (kariricode/property-inspector 3 implements
 * both). The object passed in is never modified.
 *
 * Preconditions: the inspector decides what an unreadable property means;
 * kariricode/property-inspector's ReflectionPropertyInspector throws for
 * an uninitialized one.
 *
 * Thread-safety: immutable; one AttributeRules per call.
 *
 * Side effects: none beyond the inspector's reads, the applier's copy and
 * the engine's observer.
 *
 * Example:
 *
 *     $sanitizer = new AttributeSanitizer(new SanitizerEngine(), new ReflectionPropertyInspector(), new ReflectionChangeApplier());
 *     $clean = $sanitizer->sanitize($signUp)->object;
 *
 * References: kariricode/property-inspector ADR-006, ADR-008 and ADR-009; ADR-003.
 */
final readonly class AttributeSanitizer
{
    public function __construct(
        private SanitizerEngine $engine,
        private PropertyInspector $inspector,
        private PropertyChangeApplier $applier,
    ) {}

    public function sanitize(object $object): SanitizedObject
    {
        $rules = new AttributeRules();
        $this->inspector->inspect(object: $object, attributeClass: Sanitize::class, handler: $rules);
        $values = $rules->values();
        $result = $this->engine->sanitize($values, $rules->fieldRules());
        $changes = [];

        /** @var mixed $original */
        foreach ($values as $property => $original) {
            /** @var mixed $value */
            $value = $result->get($property);

            if ($value !== $original) {
                $changes += [$property => $value];
            }
        }

        return new SanitizedObject(object: $changes === [] ? $object : $this->applier->applyChanges($object, $changes), result: $result);
    }
}
