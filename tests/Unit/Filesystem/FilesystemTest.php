<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Tests\Unit\Filesystem;

use Dbout\WpFoundation\Filesystem\Filesystem;
use Dbout\WpFoundation\Tests\TestCase;

final class FilesystemTest extends TestCase
{
    public function testDeletesTheDirectoryAndItsContent(): void
    {
        $directory = $this->createTemporaryDirectory() . '/cache';
        mkdir($directory . '/twig/ab', 0o777, true);
        file_put_contents($directory . '/twig/ab/template.php', '<?php');
        file_put_contents($directory . '/.hidden', '');

        (new Filesystem())->deleteDirectory($directory);

        self::assertDirectoryDoesNotExist($directory);
    }

    public function testDoesNothingWhenTheDirectoryDoesNotExist(): void
    {
        (new Filesystem())->deleteDirectory($this->createTemporaryDirectory() . '/missing');

        $this->expectNotToPerformAssertions();
    }

    public function testDeletesTheSymbolicLinksWithoutFollowingThem(): void
    {
        $root = $this->createTemporaryDirectory();
        mkdir($root . '/target');
        file_put_contents($root . '/target/file.txt', 'kept');
        mkdir($root . '/cache');
        symlink($root . '/target', $root . '/cache/link');

        (new Filesystem())->deleteDirectory($root . '/cache');

        self::assertDirectoryDoesNotExist($root . '/cache');
        self::assertFileExists($root . '/target/file.txt');
    }
}
