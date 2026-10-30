<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Facades;

use Dbout\WpFoundation\View\TwigServiceProvider;
use Illuminate\Support\Facades\Facade;

/**
 * @method static string|null render(string $template, array<string, mixed> $context = [])
 *
 * @see TwigServiceProvider
 */
class Twig extends Facade
{
    /**
     * @inheritDoc
     */
    protected static function getFacadeAccessor(): string
    {
        return 'twig';
    }
}
