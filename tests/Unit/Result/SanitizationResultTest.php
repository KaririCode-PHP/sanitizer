<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Tests\Unit\Result;

use KaririCode\Sanitizer\Result\FieldModification;
use KaririCode\Sanitizer\Result\SanitizationResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

// ── Unit: SanitizationResultTest ─────────────────────────────────────
// The result: values by path, what changed, the recorded changes.
// ─────────────────────────────────────────────────────────────────────

#[CoversClass(SanitizationResult::class)]
#[CoversClass(FieldModification::class)]
final class SanitizationResultTest extends TestCase
{
    #[Test]
    public function anUnchangedResultReportsNoChange(): void
    {
        $result = new SanitizationResult(original: ['a' => 1], sanitized: ['a' => 1]);

        self::assertFalse($result->wasModified());
        self::assertFalse($result->isFieldModified('a'));
        self::assertFalse($result->isFieldModified('missing'));
        self::assertSame([], $result->modifications);
        self::assertSame([], $result->modifiedFields());
        self::assertSame([], $result->modificationsFor('a'));
        self::assertSame(0, $result->modificationCount());
        self::assertSame(1, $result->get('a'));
        self::assertNull($result->get('missing'));
    }

    #[Test]
    public function changesAreQueriedByField(): void
    {
        $trim = new FieldModification(field: 'user.email', ruleName: 'string.trim', before: ' A@X ', after: 'A@X');
        $lower = new FieldModification(field: 'user.email', ruleName: 'string.lower_case', before: 'A@X', after: 'a@x');
        $int = new FieldModification(field: 'age', ruleName: 'numeric.to_int', before: '42', after: 42);
        $result = new SanitizationResult(
            original: ['user' => ['email' => ' A@X ', 'name' => 'Ana'], 'age' => '42'],
            sanitized: ['user' => ['email' => 'a@x', 'name' => 'Ana'], 'age' => 42],
            modifications: [$trim, $lower, $int],
        );

        self::assertTrue($result->wasModified());
        self::assertTrue($result->isFieldModified('user.email'));
        self::assertTrue($result->isFieldModified('age'));
        self::assertFalse($result->isFieldModified('user.name'));
        self::assertSame('a@x', $result->get('user.email'));
        self::assertSame(['user.email', 'age'], $result->modifiedFields());
        self::assertSame([$trim, $lower], $result->modificationsFor('user.email'));
        self::assertSame([$int], $result->modificationsFor('age'));
        self::assertSame([], $result->modificationsFor('user.name'));
        self::assertSame(3, $result->modificationCount());
        self::assertSame(['user.email', 'string.trim', ' A@X ', 'A@X'], [$trim->field, $trim->ruleName, $trim->before, $trim->after]);
    }

    #[Test]
    public function aValueTurnedIntoAnotherTypeCountsAsAChange(): void
    {
        $result = new SanitizationResult(original: ['age' => '42', 'flag' => 0], sanitized: ['age' => 42, 'flag' => false]);

        self::assertTrue($result->isFieldModified('age'));
        self::assertTrue($result->isFieldModified('flag'));
        self::assertTrue($result->wasModified());
    }
}
