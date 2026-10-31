<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Tests\Fixtures\Providers;

use Dbout\WpFoundation\Application;

final class RecordingProvider
{
    /**
     * @var array<string, string>
     */
    public array $bindings = [
        'recording.binding' => \stdClass::class,
    ];

    /**
     * @var array<int|string, string>
     */
    public array $singletons = [
        'recording.singleton' => \stdClass::class,
        \ArrayObject::class,
    ];

    /**
     * @var string[]
     */
    public array $calls = [];

    public function __construct(
        public readonly Application $app,
    ) {}

    public function register(): void
    {
        $this->calls[] = 'register';
    }

    public function boot(): void
    {
        $this->calls[] = 'boot';
    }
}
