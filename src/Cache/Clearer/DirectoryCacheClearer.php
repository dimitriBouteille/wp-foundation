<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Cache\Clearer;

use Dbout\WpFoundation\Filesystem\Filesystem;

/**
 * Deletes a cache directory (e.g. the compiled Twig templates).
 */
final readonly class DirectoryCacheClearer implements CacheClearerInterface
{
    public function __construct(
        private string $name,
        private string $directory,
        private Filesystem $filesystem = new Filesystem(),
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function clear(): void
    {
        $this->filesystem->deleteDirectory($this->directory);
    }
}
