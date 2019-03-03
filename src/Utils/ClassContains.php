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
}
