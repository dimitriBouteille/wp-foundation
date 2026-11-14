<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Tests\Fixtures\Modules\Shop;

final class ShopServiceProvider
{
    /**
     * @var string[]
     */
    public array $calls = [];

    public function register(): void
    {
        $this->calls[] = 'register';
    }

    public function boot(): void
    {
        $this->calls[] = 'boot';
    }
}
