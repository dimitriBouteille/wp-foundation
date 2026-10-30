<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\View\Twig\Extensions;

use Dbout\WpFoundation\Facades\Asset;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension that provides asset management capabilities within Twig templates.
 *
 * This extension exposes the framework's asset resolver functionality through two functions:
 * `asset_content` and `asset_url`, allowing templates to retrieve asset contents or URLs.
 */
final class AssetExtension extends AbstractExtension
{
    /**
     * Returns the list of Twig functions provided by this extension.
     *
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('asset_content', fn (string $filename) => Asset::getContent($filename)),
            new TwigFunction('asset_url', fn (string $filename) => Asset::resolveUrl($filename)),
        ];
    }
}
