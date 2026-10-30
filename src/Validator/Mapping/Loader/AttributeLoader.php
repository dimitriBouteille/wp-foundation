<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Validator\Mapping\Loader;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Validator\Constraints\GroupSequenceProvider;
use Symfony\Component\Validator\Exception\MappingException;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Mapping\Loader\LoaderInterface;

/**
 * Loads validation metadata using PHP attributes.
 * @see https://github.com/symfony/validator/blob/7.3/Mapping/Loader/AttributeLoader.php
 */
class AttributeLoader implements LoaderInterface
{
    public function loadClassMetadata(ClassMetadata $metadata): bool
    {
        $reflClass = $metadata->getReflectionClass();
        $className = $reflClass->name;
        $success = false;

        foreach ($this->getAttributes($reflClass) as $constraint) {
            if ($constraint instanceof GroupSequence) {
                $metadata->setGroupSequence($constraint);
            } elseif ($constraint instanceof GroupSequenceProvider) {
                //$metadata->setGroupProvider($constraint->provider);
                $metadata->setGroupSequenceProvider(true);
            } elseif ($constraint instanceof Constraint) {
                $metadata->addConstraint($constraint);
            }

            $success = true;
        }

        foreach ($reflClass->getProperties() as $property) {
            if ($property->getDeclaringClass()->name === $className) {
                foreach ($this->getAttributes($property) as $constraint) {
                    if ($constraint instanceof Constraint) {
                        $metadata->addPropertyConstraint($property->name, $constraint);
                    }

                    $success = true;
                }
            }
        }

        foreach ($reflClass->getMethods() as $method) {
            if ($method->getDeclaringClass()->name === $className) {
                foreach ($this->getAttributes($method) as $constraint) {
                    if ($constraint instanceof Callback) {
                        $constraint->callback = $method->getName();

                        $metadata->addConstraint($constraint);
                    } elseif ($constraint instanceof Constraint) {
                        if (preg_match('/^(get|is|has)(.+)$/i', $method->name, $matches)) {
                            $metadata->addGetterMethodConstraint(lcfirst($matches[2]), $matches[0], $constraint);
                        } else {
                            throw new MappingException(\sprintf('The constraint on "%s::%s()" cannot be added. Constraints can only be added on methods beginning with "get", "is" or "has".', $className, $method->name));
                        }
                    }

                    $success = true;
                }
            }
        }

        return $success;
    }

    /**
     * @param \ReflectionMethod|\ReflectionClass<object>|\ReflectionProperty $reflection
     * @return iterable<GroupSequence|GroupSequenceProvider|Constraint>
     */
    private function getAttributes(\ReflectionMethod|\ReflectionClass|\ReflectionProperty $reflection): iterable
    {
        foreach ($reflection->getAttributes(GroupSequence::class) as $attribute) {
            yield $attribute->newInstance();
        }
        foreach ($reflection->getAttributes(GroupSequenceProvider::class) as $attribute) {
            yield $attribute->newInstance();
        }
        foreach ($reflection->getAttributes(Constraint::class, \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            yield $attribute->newInstance();
        }
    }
}
