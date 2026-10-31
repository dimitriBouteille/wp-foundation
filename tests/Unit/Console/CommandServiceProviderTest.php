<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Tests\Unit\Console;

use Brain\Monkey\Actions;
use Dbout\WpFoundation\Cache\Console\ClearCacheCommand;
use Dbout\WpFoundation\Console\CommandServiceProvider;
use Dbout\WpFoundation\Tests\Doubles\Cli;
use Dbout\WpFoundation\Tests\Fixtures\Modules\Shop\Console\SyncCommand;
use Dbout\WpFoundation\Tests\TestCase;

final class CommandServiceProviderTest extends TestCase
{
    public function testRegistersTheCommandsOnCliInit(): void
    {
        $registerCommands = null;
        Actions\expectAdded('cli_init')->once()->whenHappen(function (callable $callback) use (&$registerCommands): void {
            $registerCommands = $callback;
        });
        $app = $this->createApplication([
            'console' => [
                'namespaces' => [
                    'Dbout\\WpFoundation\\*\\Console\\',
                    'Dbout\\WpFoundation\\Tests\\Fixtures\\Modules\\*\\Console\\',
                ],
            ],
        ]);

        (new CommandServiceProvider($app))->boot();
        self::assertCount(0, Cli::$commands);

        self::assertIsCallable($registerCommands);
        $registerCommands();

        self::assertSame(['app:cache:clear', 'shop:sync'], array_keys(Cli::$commands));
        self::assertInstanceOf(ClearCacheCommand::class, Cli::$commands['app:cache:clear']['callable']);
        self::assertInstanceOf(SyncCommand::class, Cli::$commands['shop:sync']['callable']);
        self::assertSame(['shortdesc' => 'Synchronize the shop.'], Cli::$commands['shop:sync']['args']);
    }
}
