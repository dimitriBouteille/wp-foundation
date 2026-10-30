<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Console\Concerns;

/**
 * Output helpers of the WP-CLI commands.
 */
trait InteractsWithOutput
{
    /**
     * Display success message prefixed with "Success: ".
     */
    protected function successMessage(string $message): void
    {
        \WP_CLI::success($message);
    }

    /**
     * Display warning message prefixed with "Warning: ".
     */
    protected function warningMessage(string $message): void
    {
        \WP_CLI::warning($message);
    }

    /**
     * Display error message prefixed with "Error: " and exit script.
     */
    protected function errorMessage(string $message, bool $exit = true): void
    {
        \WP_CLI::error($message, $exit);
    }
}
