<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Routing\View\Twig\Extensions;

use Dbout\WpFoundation\Facades\Router;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension that provides URL generation capabilities within Twig templates.
 *
 * This extension exposes the framework's router functionality through the `page_url` function,
 * allowing templates to generate URLs for named routes.
 */
final class RouterExtension extends AbstractExtension
{
    /**
     * Returns the list of Twig functions provided by this extension.
     *
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('page_url', fn (...$args) => Router::generate(...$args)),
        ];
    }
}
