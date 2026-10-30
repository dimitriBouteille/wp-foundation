<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Support;

/**
 * Provides helper methods for working with the current WordPress theme.
 */
final class Theme
{
    /**
     * Returns the full URL to a file within the theme directory.
     *
     * If no path is provided, returns the base URL of the theme directory.
     *
     * @param string $path Relative path to a file within the theme directory.
     * @return string The absolute URL to the specified theme file.
     */
    public static function url(string $path = ''): string
    {
        return trim(get_template_directory_uri(), '/') . '/' . ltrim($path, '/');
    }
}
