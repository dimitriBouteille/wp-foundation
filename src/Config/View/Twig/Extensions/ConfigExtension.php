<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Config\View\Twig\Extensions;

use Dbout\WpFoundation\Facades\Config;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension that provides access to the application configuration.
 */
final class ConfigExtension extends AbstractExtension
{
    /**
     * @inheritDoc
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('config', [Config::class, 'get']),
            new TwigFunction('config_exist', [Config::class, 'has']),
        ];
    }
}
