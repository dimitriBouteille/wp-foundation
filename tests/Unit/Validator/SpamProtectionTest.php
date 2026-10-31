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
     * The timestamp is given as a number of seconds before the submission, it is computed when the test runs:
     * the data providers are called before all the tests, a timestamp computed there would age with the suite.
     *
     * @return iterable<string, array{array<string, mixed>, int|null, bool}>
     */
    public static function submissionProvider(): iterable
    {
        yield 'human' => [['website_url' => ''], 30, false];
        yield 'honeypot filled' => [['website_url' => 'https://spam.example'], 30, true];
        yield 'too fast' => [[], 1, true];
        yield 'too old' => [[], 86401, true];
        yield 'without timestamp' => [[], null, true];
        yield 'invalid timestamp' => [['_ts' => 'yesterday'], null, true];
    }

    /**
     * @param array<string, mixed> $data
     * @param int|null $secondsAgo Time between the form load (`_ts`) and the submission, null to keep the `_ts` of the data
     */
    #[DataProvider('submissionProvider')]
    public function testDetectsTheSpam(array $data, ?int $secondsAgo, bool $isSpam): void
    {
        if ($secondsAgo !== null) {
            $data['_ts'] = time() - $secondsAgo;
        }

        self::assertSame($isSpam, SpamProtection::isSpam($data));
    }
}
