<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\View\Resolver;

interface ResolverInterface
{
    /**
     * @throws \Exception
     */
    public function resolve(string $file): ?string;

    public function getPath(string $file): ?string;

    public function getRootPath(): string;
}
