<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Tests\Doubles;

/**
 * Commands and messages recorded by the WP_CLI stub of the tests.
 */
final class Cli
{
    /**
     * @var array<string, array{callable: mixed, args: array<string, mixed>}>
     */
    public static array $commands = [];

    /**
     * @var array<array{string, string}>
     */
    public static array $messages = [];

    public static function reset(): void
    {
        self::$commands = [];
        self::$messages = [];
    }
}
