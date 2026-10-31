<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Tests\Unit\Config;

use Dbout\WpFoundation\Config\Repository;
use Dbout\WpFoundation\Tests\TestCase;

final class RepositoryTest extends TestCase
{
    public function testResolvesTheClosureValues(): void
    {
        $repository = new Repository([
            'app' => ['env' => fn (): string => 'production'],
        ]);

        self::assertSame('production', $repository->get('app.env'));
    }

    public function testReturnsTheOtherValuesAsTheyAre(): void
    {
        $repository = new Repository(['app' => ['locale' => 'fr_FR']]);

        self::assertSame('fr_FR', $repository->get('app.locale'));
        self::assertSame('default', $repository->get('app.missing', 'default'));
    }

    public function testResolvesTheClosureDefaultValue(): void
    {
        self::assertSame('default', (new Repository())->get('missing', fn (): string => 'default'));
    }
}
