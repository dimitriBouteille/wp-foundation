<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

use Dbout\WpFoundation\Tests\Doubles\Cli;

/**
 * Records the commands and the messages in {@see Cli} instead of running WP-CLI.
 */
final class WP_CLI
{
    /**
     * @param array<string, mixed> $args
     */
    public static function add_command(string $name, mixed $callable, array $args = []): bool
    {
        Cli::$commands[$name] = ['callable' => $callable, 'args' => $args];

        return true;
    }

    public static function success(string $message): void
    {
        Cli::$messages[] = ['success', $message];
    }

    public static function warning(string $message): void
    {
        Cli::$messages[] = ['warning', $message];
    }

    public static function error(string $message, bool $exit = true): void
    {
        Cli::$messages[] = [$exit ? 'error' : 'error_no_exit', $message];
    }
}
