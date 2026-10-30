<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Facades;

use Illuminate\Support\Facades\Facade;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @method static string|null generate(string $name, array<string, mixed> $parameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
 */
class Router extends Facade
{
    /**
     * @inheritDoc
     */
    protected static function getFacadeAccessor(): string
    {
        return 'router';
    }
}
