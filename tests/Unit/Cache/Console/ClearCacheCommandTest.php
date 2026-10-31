<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Tests\Unit\Cache\Console;

use Brain\Monkey\Actions;
use Dbout\WpFoundation\Cache\Clearer\CacheClearerInterface;
use Dbout\WpFoundation\Cache\Console\ClearCacheCommand;
use Dbout\WpFoundation\Tests\Doubles\Cli;
use Dbout\WpFoundation\Tests\TestCase;

final class ClearCacheCommandTest extends TestCase
{
    public function testRunsTheTaggedClearers(): void
    {
        $app = $this->createApplication();
        $first = new FakeClearer('Cache pool');
        $second = new FakeClearer('Twig templates');
        $app->instance('first', $first);
        $app->instance('second', $second);
        $app->tag(['first', 'second'], CacheClearerInterface::TAG);
        Actions\expectDone(ClearCacheCommand::CLEARED_ACTION)->once();

        (new ClearCacheCommand($app))();

        self::assertTrue($first->cleared);
        self::assertTrue($second->cleared);
        self::assertSame([
            ['success', 'Cache pool cleared.'],
            ['success', 'Twig templates cleared.'],
        ], Cli::$messages);
    }

    public function testReportsTheFailuresAndRunsTheOtherClearers(): void
    {
        $app = $this->createApplication();
        $second = new FakeClearer('Twig templates');
        $app->instance('first', new FakeClearer('Cache pool', new \RuntimeException('Unable to delete /var/cache.')));
        $app->instance('second', $second);
        $app->tag(['first', 'second'], CacheClearerInterface::TAG);

        (new ClearCacheCommand($app))();

        self::assertTrue($second->cleared);
        self::assertSame([
            ['error_no_exit', 'Cache pool: Unable to delete /var/cache.'],
            ['success', 'Twig templates cleared.'],
            ['error', 'The application cache was not entirely cleared.'],
        ], Cli::$messages);
    }
}

final class FakeClearer implements CacheClearerInterface
{
    public bool $cleared = false;

    public function __construct(
        private readonly string $name,
        private readonly ?\Throwable $exception = null,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function clear(): void
    {
        if ($this->exception instanceof \Throwable) {
            throw $this->exception;
        }

        $this->cleared = true;
    }
}
