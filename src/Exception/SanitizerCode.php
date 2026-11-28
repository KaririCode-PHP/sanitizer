<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Exception;

use KaririCode\Exception\CodeBlock;
use KaririCode\Exception\ErrorCode;

// ── Exception: SanitizerCode ─────────────────────────────────────────
// Every code this package throws, inside block SAN 3300–3399.
// ─────────────────────────────────────────────────────────────────────

/**
 * Names each failure the package can throw, inside the block
 * kariricode/exception's CODE-BLOCKS.md records for kariricode/sanitizer:
 * 3300–3319 rule definitions and the registry, 3320–3329 container
 * bindings, 3340–3349 the integrations.
 *
 * ARFA 1.3 E-003: backed because getCode() reads the integer through
 * kariricode/exception's ErrorCode; every other enum of the package is
 * pure.
 *
 * Preconditions: none; the cases are constants.
 *
 * Thread-safety: immutable.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     $error->errorCode === SanitizerCode::UnknownRuleAlias; // [SAN_3302]
 *
 * References: ADR-004; kariricode/exception CODE-BLOCKS.md.
 */
enum SanitizerCode: int implements ErrorCode
{
    case DuplicateRuleAlias = 3301;
    case UnknownRuleAlias = 3302;
    case InvalidRuleDefinition = 3303;
    case MissingRuleParameter = 3304;
    case InvalidRuleParameter = 3305;
    case NamedAttributeArguments = 3306;
    case InvalidBinding = 3320;
    case UnsupportedInput = 3340;

    #[\Override]
    public static function prefix(): string
    {
        return 'SAN';
    }

    #[\Override]
    public static function block(): CodeBlock
    {
        return new CodeBlock(3300, 3399);
    }
}
