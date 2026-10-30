<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Cache\Console;

use Dbout\WpFoundation\Application;
use Dbout\WpFoundation\Cache\Clearer\CacheClearerInterface;
use Dbout\WpFoundation\Console\Attribute\Command;
use Dbout\WpFoundation\Console\Concerns\InteractsWithOutput;

/**
 * Run the cache clearers tagged with {@see CacheClearerInterface::TAG}, then the {@see ClearCacheCommand::CLEARED_ACTION} action.
 */
#[Command(name: 'app:cache:clear', description: 'Clear the application cache.')]
final class ClearCacheCommand
{
    use InteractsWithOutput;

    public const string CLEARED_ACTION = 'app_cache_cleared';

    public function __construct(
        private readonly Application $app,
    ) {}

    public function __invoke(): void
    {
        $failed = false;
        foreach ($this->app->tagged(CacheClearerInterface::TAG) as $clearer) {
            if (!$clearer instanceof CacheClearerInterface) {
                continue;
            }

            try {
                $clearer->clear();
                $this->successMessage(sprintf('%s cleared.', $clearer->getName()));
            } catch (\Throwable $e) {
                $failed = true;
                $this->errorMessage(sprintf('%s: %s', $clearer->getName(), $e->getMessage()), exit: false);
            }
        }

        if ($failed) {
            $this->errorMessage('The application cache was not entirely cleared.');
        }

        do_action(self::CLEARED_ACTION);
    }
}
