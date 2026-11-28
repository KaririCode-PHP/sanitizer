<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Tests\Support;

use KaririCode\Sanitizer\Contract\SanitizationRule;
use KaririCode\Sanitizer\Core\RuleContext;
use KaririCode\Sanitizer\Exception\InvalidRuleException;
use KaririCode\Sanitizer\Exception\SanitizerCode;
use PHPUnit\Framework\TestCase;

// ── Support: RuleTestCase ────────────────────────────────────────────
// Shared checks of the rule suites: what comes out, and what is refused.
// ─────────────────────────────────────────────────────────────────────

/** Base of the rule suites: a context for field "field" and two assertions. */
abstract class RuleTestCase extends TestCase
{
    /**
     * @param array<string, mixed> $parameters
     */
    protected static function context(array $parameters = []): RuleContext
    {
        return new RuleContext(data: ['field' => 'x'], field: 'field', parameters: $parameters);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    protected static function assertSanitizes(mixed $expected, SanitizationRule $rule, mixed $value, array $parameters = []): void
    {
        self::assertSame($expected, $rule->sanitize($value, self::context($parameters)), get_debug_type($value) . ' ' . var_export($value, true));
    }

    /**
     * @param array<string, mixed> $parameters
     */
    protected static function assertRefused(SanitizerCode $code, SanitizationRule $rule, array $parameters, mixed $value = 'value'): void
    {
        try {
            $rule->sanitize($value, self::context($parameters));
        } catch (InvalidRuleException $exception) {
            self::assertSame($code, $exception->errorCode);
            self::assertStringContainsString('"field"', $exception->getMessage());

            return;
        }

        self::fail('The rule accepted a misconfiguration.');
    }
}
