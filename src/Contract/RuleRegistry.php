<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Contract;

// ── Contract: RuleRegistry ───────────────────────────────────────────
// Rules by alias, so definitions can name them.
// ─────────────────────────────────────────────────────────────────────

/**
 * Maps the aliases rule definitions use ('trim', 'html_encode') to rule
 * instances. Registration is part of setting the engine up; resolving
 * happens on every sanitization.
 *
 * Preconditions: an alias is registered once.
 *
 * Thread-safety: implementations decide; register before sharing.
 *
 * Side effects: register() changes the registry.
 *
 * Example:
 *
 *     $registry->register('collapse_dashes', new CollapseDashesRule());
 *     $rule = $registry->resolve('collapse_dashes');
 *
 * References: ADR-001; SPEC-002.
 */
interface RuleRegistry
{
    public function register(string $alias, SanitizationRule $rule): void;

    public function resolve(string $alias): SanitizationRule;

    public function has(string $alias): bool;

    /**
     * @return list<string>
     */
    public function aliases(): array;
}
