<?php

namespace Coosos\VersionWorkflowBundle\Utils;

/**
 * Class ClassContains
 *
 * @package Coosos\VersionWorkflowBundle\Utils
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
class ClassContains
{
    /**
     * @param mixed  $object
     * @param string $attribute
     * @return string|null
     */
    public function getGetterMethod($object, string $attribute)
    {
        if (method_exists($object, 'get' . ucfirst($attribute))) {
            return 'get' . ucfirst($attribute);
        }

        return null;
    }

    /**
     * @param mixed  $object
     * @param string $attribute
     * @return string|null
     */
    public function getSetterMethod($object, string $attribute)
    {
        if (method_exists($object, 'set' . ucfirst($attribute))) {
            return 'set' . ucfirst($attribute);
        }

        return null;
    }

    /**
     * Return true if the given object use the given trait, false if not
     *
     * @param \ReflectionClass|mixed $class
     * @param string                 $traitName
     * @param boolean                $isRecursive
     * @return bool
     * @throws \ReflectionException
     */
    public function hasTrait($class, $traitName, $isRecursive = false)
    {
        if (!$class instanceof \ReflectionClass) {
            $entityClass = get_class($class);
            $class = new \ReflectionClass($entityClass);
        }

        if (in_array($traitName, $class->getTraitNames())) {
            return true;
        }

        $parentClass = $class->getParentClass();

        if (($isRecursive === false) || ($parentClass === false) || ($parentClass === null)) {
            return false;
        }

        return $this->hasTrait($parentClass, $traitName, $isRecursive);
    }
}
