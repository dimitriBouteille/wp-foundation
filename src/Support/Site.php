<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Support;

/**
 * Information of the current WordPress site, available in the Twig templates through the `site` global:
 *
 * ```twig
 * <html lang="{{ site.language }}">
 * <a href="{{ site.url }}">{{ site.name }}</a>
 * ```
 *
 * The values are read from WordPress on each call.
 */
final class Site
{
    /**
     * Returns the title of the site (Settings > General).
     *
     * WordPress stores it with the HTML special chars encoded, they are decoded so that the value is escaped only once on output.
     *
     * @return string The decoded title, e.g. "My site"
     */
    public function getName(): string
    {
        return wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
    }

    /**
     * Returns the URL of the home page of the site.
     *
     * @return string The home URL without trailing slash, e.g. "https://example.com"
     */
    public function getUrl(): string
    {
        return home_url();
    }

    /**
     * Returns the tagline of the site (Settings > General).
     *
     * WordPress stores it with the HTML special chars encoded, they are decoded so that the value is escaped only once on output.
     *
     * @return string The decoded tagline, an empty string if it is not set
     */
    public function getDescription(): string
    {
        return wp_specialchars_decode(get_bloginfo('description'), ENT_QUOTES);
    }

    /**
     * Returns the language of the site, in the format expected by the HTML `lang` attribute.
     *
     * @return string The language code, e.g. "fr-FR" (unlike the locale "fr_FR")
     */
    public function getLanguage(): string
    {
        return get_bloginfo('language');
    }

    /**
     * Returns the charset of the site, used for the `<meta charset>` tag.
     *
     * @return string The charset, e.g. "UTF-8"
     */
    public function getCharset(): string
    {
        return get_bloginfo('charset');
    }
}
