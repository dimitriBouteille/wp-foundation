<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Facades;

use Dbout\WpFoundation\Bootstrap\LoadConfiguration;
use Illuminate\Support\Facades\Config as BaseConfig;

/**
 * @see LoadConfiguration
 * @see \Illuminate\Config\Repository
 */
class Config extends BaseConfig {}
