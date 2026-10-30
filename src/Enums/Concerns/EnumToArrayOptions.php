<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Enums\Concerns;

trait EnumToArrayOptions
{
    /**
     * @throws \Exception
     * @return array<int|string, string>
     */
    public static function toArrayOptions(bool $withEmptyValue = true): array
    {
        // @phpstan-ignore function.alreadyNarrowedType
        if (!method_exists(self::class, 'getLabel')) {
            throw new \Exception(sprintf('Please implement getLabel function in enum %s.', self::class));
        }

        $options = [];
        if ($withEmptyValue) {
            $options[''] = '';
        }

        foreach (self::cases() as $case) {
            // @phpstan-ignore method.notFound
            $options[$case->value] = $case->getLabel();
        }

        return $options;
    }
}
