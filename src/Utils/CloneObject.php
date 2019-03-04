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
        if (in_array(spl_object_hash($model), $this->alreadyHashObject)) {
           return $model;
        }

        $this->alreadyHashObject[] = spl_object_hash($model);

        $modelCloned = clone $model;
        $reflection = new \ReflectionClass(get_class($modelCloned));
        $properties = $reflection->getProperties();
        foreach ($properties as $property) {
            if (in_array($property->getName(), $ignoreProperty)) {
                continue;
            }

            if (method_exists($modelCloned, 'get' . ucfirst($property->getName()))
                && (is_object($modelCloned->{'get' . ucfirst($property->getName())}())
                    || is_array($modelCloned->{'get' . ucfirst($property->getName())}()))
            ) {
                if (is_array($modelCloned->{'get' . ucfirst($property->getName())}())
                    || $modelCloned->{'get' . ucfirst($property->getName())}() instanceof \ArrayAccess
                ) {
                    $list = [];
                    foreach ($modelCloned->{'get' . ucfirst($property->getName())}() as $item) {
                        $list[] = $this->cloneObjectRecursive($item, $ignoreProperty);
                    }

                    $modelCloned->{'set' . ucfirst($property->getName())}($list);
                } elseif (is_object($modelCloned->{'get' . ucfirst($property->getName())}())) {
                    $sModel = $modelCloned->{'get' . ucfirst($property->getName())}();
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
