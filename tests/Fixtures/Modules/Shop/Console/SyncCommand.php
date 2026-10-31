<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Tests\Fixtures\Modules\Shop\Console;

use Dbout\WpFoundation\Console\Attribute\Command;

#[Command(name: 'shop:sync', description: 'Synchronize the shop.')]
final class SyncCommand
{
    public function __invoke(): void {}
}
