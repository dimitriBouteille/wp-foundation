<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Tests\Fixtures\Validator;

use Symfony\Component\Validator\Constraints\IsTrue;

final class InvalidGetterForm
{
    #[IsTrue]
    public function check(): bool
    {
        return true;
    }
}
