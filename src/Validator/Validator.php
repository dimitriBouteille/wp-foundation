<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Validator;

use Dbout\WpFoundation\Translation\WordPressTranslator;
use Dbout\WpFoundation\Validator\Mapping\Loader\AttributeLoader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class Validator
{
    private static ?ValidatorInterface $sharedValidator = null;

    private static ?PropertyAccessor $propertyAccessor = null;

    private readonly ValidatorInterface $validator;

    public function __construct()
    {
        $this->validator = self::$sharedValidator ??= Validation::createValidatorBuilder()
            ->addLoader(new AttributeLoader())
            ->setTranslator(new WordPressTranslator())
            ->setTranslationDomain(WordPressTranslator::DOMAIN)
            ->getValidator();
    }

    /**
     * Validates a value against a constraint.
     *
     * @param string|string[]|null $groups
     * @param Constraint|Constraint[]|null $constraints
     * @throws \Exception
     */
    public function validate(
        mixed $value,
        mixed $source = null,
        string|array|null $groups = null,
        Constraint|array|null $constraints = null,
    ): ConstraintViolationList {
        if ($source !== null) {
            $this->hydrateObject($value, $source);
        }

        $result = $this->validator->validate(
            value: $value,
            constraints: $constraints,
            groups: $groups,
        );

        return new ConstraintViolationList($result);
    }

    /**
     * @throws \Exception
     */
    protected function hydrateObject(mixed &$value, mixed $source): void
    {
        if ($source instanceof \WP_REST_Request) {
            $source = $source->get_params();
        } elseif ($source instanceof Request) {
            $source = array_merge(
                $source->query->all(),
            );
        }

        if (!is_iterable($source)) {
            throw new \Exception('The source parameter must be iterable');
        }

        if (!is_object($value) && !is_array($value)) {
            throw new \Exception('The value must be an object or an array to be hydrated');
        }

        $accessor = $this->createPropertyAccess();
        foreach ($source as $key => $data) {
            try {
                $accessor->setValue($value, $key, $data);
            } catch (\Exception) {
            }
        }
    }

    protected function createPropertyAccess(): PropertyAccessor
    {
        if (self::$propertyAccessor instanceof PropertyAccessor) {
            return self::$propertyAccessor;
        }

        /** @var PropertyAccessor $accessor */
        $accessor = PropertyAccess::createPropertyAccessorBuilder()
            ->enableExceptionOnInvalidIndex()
            //->disableMagicSet()
            ->getPropertyAccessor();

        return self::$propertyAccessor = $accessor;
    }
}
