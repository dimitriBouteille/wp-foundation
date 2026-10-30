<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\View\Resolver;

class RawResolver extends AbstractResolver implements ResolverInterface
{
    /**
     * @inheritDoc
     */
    public function resolve(string $file): ?string
    {
        return $this->getManifestValue($file);
    }

    public function getPath(string $file): ?string
    {
        return null;
    }
}
