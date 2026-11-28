<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Integration;

use KaririCode\Contract\Processor\Processor;
use KaririCode\Contract\Sanitizer\Sanitizer;
use KaririCode\Sanitizer\Core\SanitizerEngine;
use KaririCode\Sanitizer\Exception\UnsupportedInputException;

// ── Integration: SanitizationProcessor ───────────────────────────────
// A sanitization step for kariricode/processor-pipeline and other contract consumers.
// ─────────────────────────────────────────────────────────────────────

/**
 * Sanitizes an array of fields against a fixed rule map behind the
 * kariricode/contract ports: as a Processor and as a Sanitizer it returns
 * the sanitized copy, so a pipeline can sanitize before it validates.
 *
 * Preconditions: the input is an array of fields; anything else throws
 * UnsupportedInputException.
 *
 * Thread-safety: immutable.
 *
 * Side effects: none beyond the engine's observer.
 *
 * Example:
 *
 *     $pipeline->addProcessor(new SanitizationProcessor($engine, ['email' => ['trim', 'lower_case']]));
 *
 * References: kariricode/contract Processor and Sanitizer; ADR-006.
 */
final readonly class SanitizationProcessor implements Processor, Sanitizer
{
    /**
     * @param array<array-key, mixed> $fieldRules field path => list of rule definitions
     */
    public function __construct(
        private SanitizerEngine $engine,
        private array $fieldRules,
    ) {}

    #[\Override]
    public function process(mixed $input): mixed
    {
        return $this->sanitize($input);
    }

    /**
     * @return array<array-key, mixed>
     */
    #[\Override]
    public function sanitize(mixed $input): array
    {
        if (!is_array($input)) {
            throw UnsupportedInputException::notAnArray(get_debug_type($input));
        }

        return $this->engine->sanitize($input, $this->fieldRules)->sanitized;
    }
}
