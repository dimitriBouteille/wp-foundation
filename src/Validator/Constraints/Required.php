<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Validator\Constraints;

use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Not blank value, the message contains the `{{ label }}` of the field when it is set.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Required extends NotBlank
{
    public ?string $label = null;

    /**
     * @param array<string, mixed>|null $options
     * @param string[]|null $groups
     * @param null $payload
     */
    public function __construct(?string $label = null, ?string $message = null, ?array $options = null, ?bool $allowNull = null, ?callable $normalizer = null, ?array $groups = null, $payload = null)
    {
        $this->label = $label !== '' ? $label : null;
        $message ??= $this->label !== null ? 'The field {{ label }} is required.' : 'The field is required.';

        parent::__construct(
            options: $options,
            message: $message,
            allowNull: $allowNull,
            normalizer: $normalizer,
            groups: $groups,
            payload: $payload,
        );
    }

    /**
     * @inheritDoc
     */
    public function validatedBy(): string
    {
        return RequiredValidator::class;
    }
}
