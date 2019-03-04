<?php

namespace Coosos\VersionWorkflowBundle\EventListener\PreSerializer;

use Coosos\VersionWorkflowBundle\Event\PreSerializerEvent;
use Coosos\VersionWorkflowBundle\Utils\ClassContains;

/**
 * Class TransformArrayCollectionToArrayListener
 *
 * @package Coosos\VersionWorkflowBundle\EventListener\PreSerializer
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
class TransformArrayCollectionToArrayListener
{
    /**
     * @var ClassContains
     */
    private $classContains;

    /**
     * TransformArrayCollectionToArrayListener constructor.
     *
     * @param ClassContains $classContains
     */
    public function __construct(ClassContains $classContains)
    {
        $this->classContains = $classContains;
    }

    /**
     * @param PreSerializerEvent $preSerializerEvent
     * @throws \ReflectionException
     */
    public function onCoososVersionWorkflowPreSerializer(PreSerializerEvent $preSerializerEvent)
    {
        $model = $preSerializerEvent->getData();
        $this->transformArrayCollectionToArrayRecursive($model);
    }

    /**
     * @param mixed $model
     * @return mixed
     * @throws \ReflectionException
     */
    protected function transformArrayCollectionToArrayRecursive($model)
    {
        if (!is_object($model)) {
            return $model;
        }

        if ($this->isArrayCollection($model)) {
            $array = $model->getIterator()->getArrayCopy();
            foreach ($array as $key => $item) {
                $array[$key] = $this->transformArrayCollectionToArrayRecursive($item);
            }

            return $array;
        }

        $reflect = new \ReflectionClass($model);
        $properties = $reflect->getProperties(
            \ReflectionProperty::IS_PUBLIC |
            \ReflectionProperty::IS_PROTECTED |
            \ReflectionProperty::IS_PRIVATE
        );

        foreach ($properties as $property) {
            $getterMethod = $this->classContains->getGetterMethod($model, $property->getName());
            $setterMethod = $this->classContains->getSetterMethod($model, $property->getName());
            if (is_null($getterMethod) || is_null($setterMethod)) {
                continue;
            }

            $getterValue = $model->{$getterMethod}();
            if (is_array($getterValue)) {
                $getterValueArray = [];
                foreach ($getterValue as $key => $value) {
                    $getterValueArray[$key] = $this->transformArrayCollectionToArrayRecursive($value);
                }

                $model->{$setterMethod}($getterValueArray);
            } else {
                $model->{$setterMethod}($this->transformArrayCollectionToArrayRecursive($getterValue));
            }
        }

        return $model;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    protected function isArrayCollection($value)
    {
        return is_object($value) && get_class($value) === 'Doctrine\Common\Collections\ArrayCollection';
    }
}
