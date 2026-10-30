<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

return [
    /**
     * Environment of the application ("local", "development", "staging" or "production"). Defaults to the WordPress environment type.
     */
    'env' => fn () => wp_get_environment_type(),

    /**
     * Locale used by the application (dates, ...). Defaults to the WordPress locale.
     */
    'locale' => fn () => determine_locale(),

    'date' => [
        /**
         * Carbon translations overriding the default ones of the locale.
         *
         * @see https://carbon.nesbot.com/docs/#api-localization
         */
        'translations' => [],
    ],
];
