<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Tests\Support;

use KaririCode\Sanitizer\Attribute\Sanitize;
use KaririCode\Sanitizer\Rule\Html\HtmlPurifyRule;

// ── Support: Profile ─────────────────────────────────────────────────
// A readonly DTO with #[Sanitize], as an application writes one.
// ─────────────────────────────────────────────────────────────────────

/** A profile form: chained rules, parameters, a rule instance, an untouched property. */
final readonly class Profile
{
    public function __construct(
        #[Sanitize('trim', 'lower_case')]
        public string $email,
        #[Sanitize('normalize_whitespace')]
        #[Sanitize('capitalize')]
        public string $name,
        #[Sanitize(new HtmlPurifyRule(), ['truncate', ['max' => 20, 'suffix' => '…']])]
        public string $bio = '',
        #[Sanitize('format_cnpj')]
        public ?string $cnpj = null,
        public string $notSanitized = '  kept  ',
    ) {}
}
