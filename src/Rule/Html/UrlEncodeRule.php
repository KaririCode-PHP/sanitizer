<?php

declare(strict_types=1);

namespace KaririCode\Sanitizer\Rule\Html;

use KaririCode\Sanitizer\Contract\SanitizationContext;
use KaririCode\Sanitizer\Contract\SanitizationRule;

// ── Rule: UrlEncodeRule ──────────────────────────────────────────────
// Percent-encodes a string for a URL.
// ─────────────────────────────────────────────────────────────────────

/**
 * Encodes for a query string (urlencode(), spaces as +), or for a path
 * segment with 'raw' true (rawurlencode(), RFC 3986, spaces as %20).
 *
 * Preconditions: 'raw', when given, is a boolean.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['q' => ['url_encode'], 'segment' => [['url_encode', ['raw' => true]]]]
 *
 * References: RFC 3986 §2.1; PHP urlencode(), rawurlencode(); SPEC-002 §2.
 */
final readonly class UrlEncodeRule implements SanitizationRule
{
    #[\Override]
    public function sanitize(mixed $value, SanitizationContext $context): mixed
    {
        $raw = $context->boolParameter('raw', false);

        if (!is_string($value)) {
            return $value;
        }

        return $raw ? rawurlencode($value) : urlencode($value);
    }

    #[\Override]
    public function name(): string
    {
        return 'html.url_encode';
    }
}
