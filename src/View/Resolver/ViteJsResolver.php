<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\View\Resolver;

class ViteJsResolver extends AbstractResolver implements ResolverInterface
{
    public function __construct(
        private readonly string $publicPath,
        string $root,
        string $manifest = 'manifest.json',
        private readonly string $entry = 'index.html',
    ) {
        parent::__construct(
            manifest: $manifest,
            root: $root,
        );
    }

    /**
     * @inheritDoc
     */
    public function resolve(string $file): ?string
    {
        if ($file === 'main.js') {
            return $this->aroundPublic($this->getManifestValue($this->entry, 'file'));
        }

        if ($file === 'main.css') {
            return $this->aroundPublic($this->getManifestValue($this->entry, 'css', 0));
        }

        if (preg_match("#\.(jpg|jpeg|gif|png|svg|webp)$#i", $file) === 1) {
            return $this->aroundPublic($file);
        }

        return null;
    }

    public function getPath(string $file): ?string
    {
        if (str_ends_with($file, '.svg')) {
            return $this->root . DIRECTORY_SEPARATOR . ltrim($file, '/');
        }

        return null;
    }

    private function aroundPublic(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        return rtrim($this->publicPath, '/') . '/' . ltrim($path, '/');
    }
}
