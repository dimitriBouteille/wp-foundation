<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Image;

final class ImageServiceProvider
{
    public function boot(): void
    {
        $cleaner = new GeneratedImagesCleaner();
        add_action('delete_attachment', [$cleaner, 'onDelete']);
        add_filter('wp_generate_attachment_metadata', [$cleaner, 'onRegenerate'], 10, 2);
    }
}
