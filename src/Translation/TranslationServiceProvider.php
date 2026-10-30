<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Translation;

/**
 * Loads the translations of the framework (`languages/wp-foundation-{locale}.mo`) for the current locale.
 */
final class TranslationServiceProvider
{
    public function boot(): void
    {
        add_action('init', [$this, 'loadTextDomain'], 0);
        add_action('change_locale', [$this, 'loadTextDomain']);
    }

    public function loadTextDomain(): void
    {
        unload_textdomain(WordPressTranslator::DOMAIN);
        load_textdomain(
            WordPressTranslator::DOMAIN,
            sprintf('%s/languages/%s-%s.mo', dirname(__DIR__, 2), WordPressTranslator::DOMAIN, determine_locale()),
        );
    }
}
