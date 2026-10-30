<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Console\Attribute;

/**
 * Registers the class as a WP-CLI command, see {@see \Dbout\WpFoundation\Console\CommandServiceProvider}.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
readonly class Command
{
    /**
     * @param string $name                  The command name (e.g. "app:cache:clear")
     * @param string|null $description      The short description displayed by `wp help`
     */
    public function __construct(
        public string $name,
        public ?string $description = null,
    ) {}
}
