<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\View\Resolver;

abstract class AbstractResolver implements ResolverInterface
{
    /**
     * @var array<mixed>|null
     */
    private ?array $manifestContent = null;

    public function __construct(
        protected readonly string $manifest,
        protected readonly string $root,
    ) {}

    /**
     * @inheritDoc
     */
    public function getRootPath(): string
    {
        return $this->root;
    }

    protected function getManifestPath(): string
    {
        return rtrim($this->root, '/') . DIRECTORY_SEPARATOR . ltrim($this->manifest, '/');
    }

    /**
     * Returns the string found by following the keys in the manifest, e.g. `getManifestValue('main.js', 'css', 0)`.
     *
     * @throws \Exception
     */
    protected function getManifestValue(string|int ...$keys): ?string
    {
        $value = $this->getManifest();
        foreach ($keys as $key) {
            if (!is_array($value)) {
                return null;
            }

            $value = $value[$key] ?? null;
        }

        return is_string($value) ? $value : null;
    }

    /**
     * @throws \Exception
     * @return array<mixed>
     */
    protected function getManifest(): array
    {
        if (is_array($this->manifestContent)) {
            return $this->manifestContent;
        }

        $path = $this->getManifestPath();
        if (!file_exists($path)) {
            throw new \Exception(sprintf('Manifest %s file not found.', $path));
        }

        $content = file_get_contents($path);
        if ($content === false) {
            throw new \Exception(sprintf('File %s is invalid.', $path));
        }

        $manifest = \json_decode($content, true);
        if (!is_array($manifest)) {
            throw new \Exception(sprintf('File %s is invalid.', $path));
        }

        return $this->manifestContent = $manifest;
    }
}
