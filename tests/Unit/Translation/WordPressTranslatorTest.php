<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Tests\Unit\Translation;

use Brain\Monkey\Functions;
use Dbout\WpFoundation\Tests\TestCase;
use Dbout\WpFoundation\Translation\WordPressTranslator;

final class WordPressTranslatorTest extends TestCase
{
    public function testTranslatesWithTheDomainOfThePackage(): void
    {
        Functions\expect('translate')->once()->with('The field is required.', 'wp-foundation')->andReturn('Le champ est obligatoire.');

        self::assertSame('Le champ est obligatoire.', (new WordPressTranslator())->trans('The field is required.'));
    }

    public function testTranslatesWithTheGivenDomain(): void
    {
        Functions\expect('translate')->once()->with('Hello', 'my-theme')->andReturn('Bonjour');

        self::assertSame('Bonjour', (new WordPressTranslator())->trans('Hello', domain: 'my-theme'));
    }

    public function testAppliesTheParametersAndThePluralization(): void
    {
        Functions\when('translate')->returnArg();
        $translator = new WordPressTranslator();

        self::assertSame('The field Email is required.', $translator->trans('The field {{ label }} is required.', ['{{ label }}' => 'Email']));
        self::assertSame('1 file', $translator->trans('%count% file|%count% files', ['%count%' => 1]));
        self::assertSame('3 files', $translator->trans('%count% file|%count% files', ['%count%' => 3]));
    }
}
