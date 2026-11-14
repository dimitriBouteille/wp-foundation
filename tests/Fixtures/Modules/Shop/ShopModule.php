<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Tests\Fixtures\Modules\Shop;

use Dbout\WpFoundation\Module\AbstractModule;
use Dbout\WpFoundation\Tests\Fixtures\Modules\Blog\BlogModule;
use Dbout\WpFoundation\Tests\Fixtures\Modules\UserAccount\UserAccountModule;

final class ShopModule extends AbstractModule
{
    public function getDependencies(): array
    {
        return [BlogModule::class, UserAccountModule::class];
    }

    public function getProviders(): array
    {
        return [ShopServiceProvider::class];
    }

    public function getConfiguration(): array
    {
        return array_merge_recursive(parent::getConfiguration(), [
            'shop.handlers' => $this->pathIfExists('Controller/Admin'),
        ]);
    }
}
