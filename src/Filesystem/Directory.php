<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Filesystem;

/**
 * Directories used by the framework.
 *
 * Each case maps to a default path relative to the application root, which can be replaced by a custom
 * absolute path ({@see \Dbout\WpFoundation\Configuration\ApplicationBuilder::withDirectory()}).
 * The project can define its own directories with an enum implementing {@see DirectoryInterface}.
 */
enum Directory implements DirectoryInterface
{
    /** Application root directory */
    case Root;

    /** Variable data directory for runtime files */
    case Var;

    /** Cache storage directory */
    case Cache;

    /** Log files directory */
    case Log;

    /**
     * @inheritDoc
     */
    public function getPath(): string
    {
        return match ($this) {
            self::Root => '',
            self::Var => 'var',
            self::Cache => 'var/cache',
            self::Log => 'var/logs',
        };
    }
}
