<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Validator\Constraints;

use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Validator\Constraints\DateTimeValidator;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class Date extends DateTime
{
    /**
     * @var string
     */
    public $format = 'd-m-Y';

    /**
     * @var string
     */
    public $message = 'The date does not have the right format.';

    /**
     * @inheritDoc
     */
    public function validatedBy()
    {
        return DateTimeValidator::class;
    }
}
