<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Filesystem;

class Filesystem
{
    /**
     * Delete a directory and its content, the symbolic links are deleted without following them.
     *
     * @throws \RuntimeException When a file or a directory cannot be deleted
     */
    public function deleteDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo) {
                continue;
            }

            $path = $file->getPathname();
            $deleted = $file->isDir() && !$file->isLink() ? @rmdir($path) : @unlink($path);
            if (!$deleted) {
                throw new \RuntimeException(sprintf('Unable to delete %s.', $path));
            }
        }

        if (!@rmdir($directory)) {
            throw new \RuntimeException(sprintf('Unable to delete %s.', $directory));
        }
    }
}
