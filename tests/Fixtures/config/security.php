<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

return [
    'hideVersion' => true,
    'headers' => [
        'Permissions-Policy' => 'camera=()',
    ],
    'restApi' => [
        'restricted' => true,
    ],
];
