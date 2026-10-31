<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Tests\Unit\Validator;

use Dbout\WpFoundation\Tests\TestCase;
use Dbout\WpFoundation\Validator\SpamProtection;
use PHPUnit\Framework\Attributes\DataProvider;

final class SpamProtectionTest extends TestCase
{
    /**
     * @return iterable<string, array{array<string, mixed>, bool}>
     */
    public static function submissionProvider(): iterable
    {
        yield 'human' => [['website_url' => '', '_ts' => time() - 30], false];
        yield 'honeypot filled' => [['website_url' => 'https://spam.example', '_ts' => time() - 30], true];
        yield 'too fast' => [['_ts' => time() - 1], true];
        yield 'too old' => [['_ts' => time() - 86401], true];
        yield 'without timestamp' => [[], true];
        yield 'invalid timestamp' => [['_ts' => 'yesterday'], true];
    }

    /**
     * @param array<string, mixed> $data
     */
    #[DataProvider('submissionProvider')]
    public function testDetectsTheSpam(array $data, bool $isSpam): void
    {
        self::assertSame($isSpam, SpamProtection::isSpam($data));
    }
}
