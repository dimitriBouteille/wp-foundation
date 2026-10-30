<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Config;

use Illuminate\Config\Repository as BaseRepository;

class Repository extends BaseRepository
{
    /**
     * @inheritDoc
     * @param array<int|string, mixed>|string $key
     */
    public function get($key, $default = null): mixed
    {
        $value = parent::get($key, $default);

        if ($value instanceof \Closure) {
            return $value();
        }

        return $value;
    }
}
