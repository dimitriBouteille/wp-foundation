<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Translation;

use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Contracts\Translation\TranslatorTrait;

/**
 * Symfony translator using the WordPress translations (e.g. to translate the validation messages).
 *
 * The message is translated with `translate()`, then the parameters and the pluralization (`%count%`) are applied
 * like the Symfony translator.
 */
final class WordPressTranslator implements TranslatorInterface
{
    use TranslatorTrait {
        trans as private applyParameters;
    }

    public const string DOMAIN = 'wp-foundation';

    /**
     * @param array<string, mixed> $parameters
     */
    public function trans(?string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        $id = (string) $id;

        return $this->applyParameters(translate($id, $domain ?? self::DOMAIN), $parameters, $domain, $locale);
    }
}
