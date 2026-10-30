<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\View;

/**
 * Builds an escaped HTML attribute string, safe to output with Twig `|raw`.
 *
 * - `['disabled' => true]` renders `disabled`, `false` and `null` are skipped.
 * - `[0 => 'novalidate']` renders `novalidate` (value-less attribute).
 * - `['value' => false]` renders `value=""`: booleans are kept for the `value` attribute.
 */
final class HtmlAttributes
{
    /**
     * @param array<int|string, mixed> $attributes
     */
    public static function render(array $attributes): string
    {
        $html = [];
        foreach ($attributes as $name => $value) {
            $attribute = self::attribute($name, $value);
            if ($attribute !== null) {
                $html[] = $attribute;
            }
        }

        return implode(' ', $html);
    }

    public static function attribute(int|string $name, mixed $value): ?string
    {
        if (is_int($name)) {
            return is_string($value) && $value !== '' ? self::escape($value) : null;
        }

        if ($value === null) {
            return null;
        }

        if (is_bool($value) && $name !== 'value') {
            return $value ? self::escape($name) : null;
        }

        if (!is_scalar($value) && !$value instanceof \Stringable) {
            return null;
        }

        return sprintf('%s="%s"', self::escape($name), self::escape((string) $value));
    }

    private static function escape(string $value): string
    {
        // Same behaviour as WordPress esc_attr(): existing entities are not double-encoded
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8', false);
    }
}
