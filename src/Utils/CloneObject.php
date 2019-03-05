<?php

namespace Coosos\VersionWorkflowBundle\Utils;

/**
 * Class CloneObject
 *
 * @package Coosos\VersionWorkflowBundle\Utils
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
class CloneObject
{
    /**
     * @var array
     */
    public $alreadyHashObject;

    /**
     * @var ClassContains
     */
    private $classContains;

    /**
     * CloneObject constructor.
     *
     * @param ClassContains $classContains
     */
    public function __construct(ClassContains $classContains)
    {
        $this->classContains = $classContains;
    }

    /**
     * @param $model
     * @param $ignoreProperty
     * @return mixed
     * @throws \ReflectionException
     */
    public function cloneObject($model, $ignoreProperty)
    {
        $this->alreadyHashObject = [];

        return $this->cloneObjectRecursive($model, $ignoreProperty);
    }

    /**
     * @param $model
     * @param $ignoreProperty
     * @return mixed
     * @throws \ReflectionException
     */
    protected function cloneObjectRecursive($model, $ignoreProperty)
    {
        $splObjectHash = spl_object_hash($model);

        if (isset($this->alreadyHashObject[$splObjectHash])) {
            return $this->alreadyHashObject[$splObjectHash];
        }

        $modelCloned = clone $model;
        $this->alreadyHashObject[$splObjectHash] = $modelCloned;

        $reflection = new \ReflectionClass(get_class($modelCloned));
        $properties = $reflection->getProperties();
        foreach ($properties as $property) {
            if (in_array($property->getName(), $ignoreProperty)) {
                continue;
            }

            $getterMethod = $this->classContains->getGetterMethod($modelCloned, $property->getName());
            $setterMethod = $this->classContains->getSetterMethod($modelCloned, $property->getName());
            if (!is_null($getterMethod)
                && (is_object($modelCloned->{$getterMethod}())
                    || is_array($modelCloned->{$getterMethod}()))
            ) {
                if (is_array($modelCloned->{$getterMethod}())
                    || $modelCloned->{$getterMethod}() instanceof \ArrayAccess
                ) {
                    $list = [];
                    foreach ($modelCloned->{$getterMethod}() as $item) {
                        $list[] = $this->cloneObjectRecursive($item, $ignoreProperty);
                    }

                    $modelCloned->{$setterMethod}($list);
                } elseif (is_object($modelCloned->{$getterMethod}())) {
                    $sModel = $modelCloned->{$getterMethod}();
                    $cloneResult = $this->cloneObjectRecursive($sModel, $ignoreProperty);

                    $reflectProperty = new \ReflectionProperty(get_class($modelCloned), $property->getName());
                    $reflectProperty->setAccessible(true);
                    $reflectProperty->setValue($modelCloned, $cloneResult);
                }
            }
        }

        return $modelCloned;
    }
}
