<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Tests\Unit\Cache\Clearer;

use Dbout\WpFoundation\Cache\Clearer\DirectoryCacheClearer;
use Dbout\WpFoundation\Cache\Clearer\PoolCacheClearer;
use Dbout\WpFoundation\Tests\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class CacheClearerTest extends TestCase
{
    public function testThePoolClearerClearsThePool(): void
    {
        $pool = new ArrayAdapter();
        $pool->save($pool->getItem('key')->set('value'));

        $clearer = new PoolCacheClearer($pool);
        $clearer->clear();

        self::assertSame('Cache pool', $clearer->getName());
        self::assertFalse($pool->hasItem('key'));
    }

    public function testThePoolClearerThrowsWhenThePoolIsNotCleared(): void
    {
        $pool = $this->createStub(CacheItemPoolInterface::class);
        $pool->method('clear')->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to clear the cache pool.');

        (new PoolCacheClearer($pool))->clear();
    }

    public function testTheDirectoryClearerDeletesTheDirectory(): void
    {
        $directory = $this->createTemporaryDirectory() . '/twig';
        mkdir($directory);
        file_put_contents($directory . '/template.php', '<?php');

        $clearer = new DirectoryCacheClearer('Twig templates', $directory);
        $clearer->clear();

        self::assertSame('Twig templates', $clearer->getName());
        self::assertDirectoryDoesNotExist($directory);
    }
}
