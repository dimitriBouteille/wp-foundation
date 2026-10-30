<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

use Dbout\WpFoundation\Facades\Dir;
use Dbout\WpFoundation\Filesystem\Directory;

return [
    /**
     * This option defines the default log channel that is utilized to write messages to your logs.
     * The value provided here should match one of the channels present in the list of "channels" configured below.
     */
    'default' => 'app',

    /**
     * Here you may configure the log channels for your application. Laravel utilizes the Monolog PHP logging library,
     * which includes a variety of powerful log handlers and formatters that you're free to use.
     */
    'channels' => [
        'app' => [
            'driver' => 'daily',
            'name' => 'app',
            'ignore_exceptions' => false,
            'path' => Dir::getPath(Directory::Log, 'app.log'),
            'level' => 'debug',
            'days' => 25,
        ],
    ],
];
