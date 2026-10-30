<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

return [
    /**
     * List of extensions added to Twig.
     *
     * @see https://twig.symfony.com/doc/3.x/advanced.html#creating-an-extension
     */
    'extensions' => [
        Dbout\WpFoundation\View\Twig\Extensions\AssetExtension::class,
        Dbout\WpFoundation\Image\View\Twig\Extensions\ImageExtension::class,
        Dbout\WpFoundation\View\Twig\Extensions\WordPressExtension::class,
    ],

    /**
     * Glob patterns of the module directories whose `views` folder is registered as a Twig namespace
     * (camelCase name of the module directory). A later module overrides the templates of an earlier one with the same namespace.
     */
    'modules' => [],
];
