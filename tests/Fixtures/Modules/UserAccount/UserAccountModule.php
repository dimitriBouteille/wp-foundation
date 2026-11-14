<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Tests\Fixtures\Modules\UserAccount;

use Dbout\WpFoundation\Module\AbstractModule;
use Dbout\WpFoundation\Tests\Fixtures\Modules\Shop\ShopModule;

final class UserAccountModule extends AbstractModule
{
    public function getDependencies(): array
    {
        return [ShopModule::class];
    }
}
