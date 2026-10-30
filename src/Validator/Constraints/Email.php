<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Validator\Constraints;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class Email extends \Symfony\Component\Validator\Constraints\Email
{
    /**
     * @param array<string, mixed> $options
     * @param string[]|null $groups
     */
    public function __construct(array $options = [], ?array $groups = null)
    {
        parent::__construct(
            options: array_merge(['message' => 'The email address is not valid.'], $options),
            groups: $groups,
        );
    }

    /**
     * @inheritDoc
     */
    public function validatedBy(): string
    {
        return EmailValidator::class;
    }
}
