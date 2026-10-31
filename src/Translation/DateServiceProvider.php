<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Translation;

use Carbon\Carbon;
use Dbout\WpFoundation\Application;

/**
 * Sets the locale of the dates (Carbon) from the `app.locale` config, and the `app.date.translations` of this locale.
 *
 * The application is created by a mu-plugin, before the plugins (WPML, Polylang) set the language of the request and
 * before the current user is known (language of the user in the administration): the locale is set when the provider
 * is registered, then again on `init` (like the translations of the framework), and follows `switch_to_locale()`.
 */
final readonly class DateServiceProvider
{
    public function __construct(
        private Application $app,
    ) {}

    /**
     * @throws \Throwable
     */
    public function register(): void
    {
        $this->applyLocale();
    }

    public function boot(): void
    {
        add_action('init', fn () => $this->applyLocale(), 0);
        // Back to the original locale (restore_previous_locale()), the `app.locale` config is set again
        add_action('change_locale', fn (string $locale) => $this->applyLocale(is_locale_switched() ? $locale : null));
    }

    /**
     * @param string|null $locale Locale to set (e.g. given by `change_locale`), null for the `app.locale` config
     * @throws \Throwable
     */
    public function applyLocale(?string $locale = null): void
    {
        $config = $this->app->getConfig();
        $configLocale = $config->get('app.locale');
        $configLocale = is_string($configLocale) && $configLocale !== '' ? $configLocale : null;
        $locale ??= $configLocale;
        if ($locale === null) {
            return;
        }

        Carbon::setLocale($locale);

        // The translations of the config replace the ones of the configured locale only, not of a locale switched to
        $translations = $config->get('app.date.translations', []);
        if ($locale === $configLocale && is_array($translations) && $translations !== []) {
            // @phpstan-ignore-next-line
            Carbon::getTranslator()->setTranslations($translations);
        }
    }
}
