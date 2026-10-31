<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Tests\Unit\Filesystem;

use Dbout\WpFoundation\Filesystem\Directory;
use Dbout\WpFoundation\Filesystem\DirectoryList;
use Dbout\WpFoundation\Tests\TestCase;

final class DirectoryListTest extends TestCase
{
    public function testResolvesTheDefaultPathsFromTheRoot(): void
    {
        $directories = new DirectoryList('/var/www/project');

        self::assertSame('/var/www/project', $directories->getRoot());
        self::assertSame('/var/www/project/', $directories->getPath(Directory::Root));
        self::assertSame('/var/www/project/var/cache/', $directories->getPath(Directory::Cache));
        self::assertSame('/var/www/project/var/logs/app.log', $directories->getPath(Directory::Log, '/app.log'));
    }

    public function testACustomPathOnlyMovesItsDirectory(): void
    {
        $directories = new DirectoryList('/var/www/project', [[Directory::Var, '/tmp/var/']]);

        self::assertSame('/tmp/var/data', $directories->getPath(Directory::Var, 'data'));
        self::assertSame('/var/www/project/var/cache/data', $directories->getPath(Directory::Cache, 'data'));
    }

    public function testNormalizesTheBackslashes(): void
    {
        $directories = new DirectoryList('C:\\www\\project', [[Directory::Log, 'D:\\logs']]);

        self::assertSame('C:/www/project', $directories->getRoot());
        self::assertSame('D:/logs/app.log', $directories->getPath(Directory::Log, 'app.log'));
    }
}
