<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Tests\Integration;

use ArrayObject;
use KaririCode\Contract\Processor\Processor;
use KaririCode\Sanitizer\Contract\SanitizationContext;
use KaririCode\Sanitizer\Contract\SanitizationRule;
use KaririCode\Sanitizer\Core\AttributeSanitizer;
use KaririCode\Sanitizer\Core\InMemoryRuleRegistry;
use KaririCode\Sanitizer\Core\SanitizerEngine;
use KaririCode\Sanitizer\Event\SanitizationCompletedEvent;
use KaririCode\Sanitizer\Integration\SanitizationProcessor;
use KaririCode\Sanitizer\Tests\Support\Profile;
use KaririCode\Sanitizer\Tests\Support\ReflectionApplier;
use KaririCode\Sanitizer\Tests\Support\ReflectionInspector;
use LogicException;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

// ── Integration: ContactFormFlowTest ─────────────────────────────────
// A contact form cleaned end to end, in a pipeline and as a DTO.
// ─────────────────────────────────────────────────────────────────────

#[CoversNothing]
final class ContactFormFlowTest extends TestCase
{
    #[Test]
    public function aCompanyContactFormIsCleanedWithACustomRule(): void
    {
        $registry = InMemoryRuleRegistry::withDefaults();
        $registry->register('mobile_br', new readonly class implements SanitizationRule {
            public function sanitize(mixed $value, SanitizationContext $context): mixed
            {
                $digits = is_string($value) ? (string) preg_replace('/\D/', '', $value) : '';

                return strlen($digits) === 11 ? sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 5), substr($digits, 7)) : $value;
            }

            public function name(): string
            {
                return 'app.mobile_br';
            }
        });
        $durations = new ArrayObject();
        $engine = new SanitizerEngine(registry: $registry, observer: static function (object $event) use ($durations): void {
            if ($event instanceof SanitizationCompletedEvent) {
                $durations->append($event->durationMs);
            }
        });
        $rules = [
            'company.name' => ['strip_non_printable', 'normalize_whitespace'],
            'company.document' => ['format_cnpj'],
            'owner.email' => ['email_filter'],
            'owner.phone' => ['mobile_br'],
            'owner.birth' => ['normalize_date'],
            'message' => ['html_purify', ['truncate', ['max' => 40, 'suffix' => '…']]],
            'quantity' => ['to_int', ['clamp', ['min' => 1, 'max' => 99]]],
            'newsletter' => ['to_bool'],
            'address.cep' => ['format_cep'],
            'address.complement' => ['trim'],
        ];
        $input = [
            'company' => ['name' => "  Kariri\u{200B} Code \t Ltda ", 'document' => '12abc34501de35'],
            'owner' => ['email' => ' Ana.Silva@Example.COM ', 'phone' => '88 99999 0000', 'birth' => '07/03/1990'],
            'message' => '<p>Olá! &lt;script&gt;alert(1)&lt;/script&gt;Gostaria de um orçamento para o projeto.</p>',
            'quantity' => '150',
            'newsletter' => 'yes',
            'address' => ['cep' => '63100000', 'city' => 'Crato'],
        ];

        $result = $engine->sanitize($input, $rules);

        self::assertSame([
            'company' => ['name' => 'Kariri Code Ltda', 'document' => '12.ABC.345/01DE-35'],
            'owner' => ['email' => 'ana.silva@example.com', 'phone' => '(88) 99999-0000', 'birth' => '1990-03-07'],
            'message' => 'Olá! Gostaria de um orçamento para o pr…',
            'quantity' => 99,
            'newsletter' => true,
            'address' => ['cep' => '63100-000', 'city' => 'Crato'],
        ], $result->sanitized);
        self::assertSame(['company.name', 'company.document', 'owner.email', 'owner.phone', 'owner.birth', 'message', 'quantity', 'newsletter', 'address.cep'], $result->modifiedFields());
        self::assertSame(['numeric.to_int', 'numeric.clamp'], array_map(static fn($change): string => $change->ruleName, $result->modificationsFor('quantity')));
        self::assertSame($input, $result->original);
        self::assertSame($result->sanitized, $engine->sanitize($result->sanitized, $rules)->sanitized, 'sanitizing twice changes nothing more');
        self::assertCount(2, $durations);
    }

    #[Test]
    public function aPipelineSanitizesBeforeItChecks(): void
    {
        $check = new readonly class implements Processor {
            public function process(mixed $input): mixed
            {
                if (!is_array($input) || filter_var($input['email'] ?? null, FILTER_VALIDATE_EMAIL) === false) {
                    throw new LogicException('The email is not valid.');
                }

                return $input;
            }
        };
        $pipeline = [new SanitizationProcessor(new SanitizerEngine(), ['email' => ['trim', 'lower_case'], 'tags' => ['to_array']]), $check];
        $run = static function (mixed $input) use ($pipeline): mixed {
            foreach ($pipeline as $processor) {
                $input = $processor->process($input);
            }

            return $input;
        };

        self::assertSame(['email' => 'ana@example.com', 'tags' => ['php']], $run(['email' => "  ANA@Example.com\n", 'tags' => 'php']));
        $this->expectExceptionObject(new LogicException('The email is not valid.'));
        $run(['email' => ' ana at example ']);
    }

    #[Test]
    public function aDtoIsCleanedIntoACopyThatNeedsNoMoreCleaning(): void
    {
        $sanitizer = new AttributeSanitizer(new SanitizerEngine(), new ReflectionInspector(), new ReflectionApplier());
        $profile = new Profile(email: ' ANA@Example.com ', name: 'ana  maria', bio: '&lt;b&gt;Dev&lt;/b&gt; no Cariri, curiosa por compiladores', cnpj: '11222333000181');

        $copy = $sanitizer->sanitize($profile)->object;
        $again = $sanitizer->sanitize($copy);

        self::assertInstanceOf(Profile::class, $copy);
        self::assertSame(['ana@example.com', 'Ana Maria', 'Dev no Cariri, curi…', '11.222.333/0001-81'], [$copy->email, $copy->name, $copy->bio, $copy->cnpj]);
        self::assertSame(' ANA@Example.com ', $profile->email);
        self::assertSame($copy, $again->object);
        self::assertFalse($again->result->wasModified());
    }
}
