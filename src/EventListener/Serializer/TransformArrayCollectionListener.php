<?php

namespace Coosos\VersionWorkflowBundle\EventListener\Serializer;

use Coosos\VersionWorkflowBundle\Event\PreSerializeEvent;
use Coosos\VersionWorkflowBundle\Utils\ClassContains;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

/**
 * Class TransformArrayCollection
 *
 * Transform doctrine array collection to array
 *
 * @package Coosos\VersionWorkflowBundle\EventListener\Serializer
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
class TransformArrayCollectionListener
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
     * @param PreSerializeEvent $preSerializeEvent
     * @throws ReflectionException
     */
    public function onCoososVersionWorkflowPreSerialize(PreSerializeEvent $preSerializeEvent)
    {
        $model = $preSerializeEvent->getData();
        $this->transformArrayCollectionToArrayRecursive($model);
    }

    /**
     * @param mixed $model
     * @return mixed
     * @throws ReflectionException
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

        $reflect = new ReflectionClass($model);
        $properties = $reflect->getProperties(
            ReflectionProperty::IS_PUBLIC |
            ReflectionProperty::IS_PROTECTED |
            ReflectionProperty::IS_PRIVATE
        );

        foreach ($properties as $property) {
            $property->setAccessible(true);
            $propertyValue = $property->getValue($model);

            if (!is_null($propertyValue)) {
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
