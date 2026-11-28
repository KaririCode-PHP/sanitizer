<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Result;

use KaririCode\Sanitizer\Core\DataPath;

// ── Result: SanitizationResult ───────────────────────────────────────
// The input, its sanitized copy and every change between them.
// ─────────────────────────────────────────────────────────────────────

/**
 * Holds the input as it came, the sanitized copy with the same shape, and
 * the changes the rules made in order. Fields read by dotted path.
 * modifiedFields(), modificationsFor() and modificationCount() read the
 * record of changes, which is empty when tracking is off; wasModified()
 * and isFieldModified() compare the data itself.
 *
 * Preconditions: none.
 *
 * Thread-safety: immutable.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     $result = $engine->sanitize(['email' => ' Ana@Example.com '], ['email' => ['trim', 'lower_case']]);
 *     $result->get('email');           // 'ana@example.com'
 *     $result->modificationsFor('email'); // trim, then lower_case
 *
 * References: ADR-002; SPEC-001 §6.
 */
final readonly class SanitizationResult
{
    /**
     * @param array<array-key, mixed> $original
     * @param array<array-key, mixed> $sanitized
     * @param list<FieldModification> $modifications
     */
    public function __construct(
        public array $original,
        public array $sanitized,
        public array $modifications = [],
    ) {}

    public function get(string $field): mixed
    {
        return DataPath::value($this->sanitized, $field);
    }

    public function wasModified(): bool
    {
        return $this->original !== $this->sanitized;
    }

    public function isFieldModified(string $field): bool
    {
        return DataPath::value($this->original, $field) !== DataPath::value($this->sanitized, $field);
    }

    /**
     * @return list<string> the fields changes were recorded for, in the order they changed
     */
    public function modifiedFields(): array
    {
        return array_values(array_unique(array_map(static fn(FieldModification $change): string => $change->field, $this->modifications)));
    }

    /**
     * @return list<FieldModification>
     */
    public function modificationsFor(string $field): array
    {
        return array_values(array_filter($this->modifications, static fn(FieldModification $change): bool => $change->field === $field));
    }

    public function modificationCount(): int
    {
        return count($this->modifications);
    }
}
