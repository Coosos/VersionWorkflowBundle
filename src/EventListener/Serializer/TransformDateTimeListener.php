<?php

namespace Coosos\VersionWorkflowBundle\EventListener\Serializer;

use Coosos\VersionWorkflowBundle\Event\PreDeserializeEvent;
use Coosos\VersionWorkflowBundle\Event\PreSerializeEvent;
use Coosos\VersionWorkflowBundle\Utils\ClassContains;

/**
 * Class TransformDateTimeListener
 *
 * @package Coosos\VersionWorkflowBundle\EventListener\Serializer
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
class TransformDateTimeListener
{
    /**
     * @var ClassContains
     */
    private $classContains;

    /**
     * TransformDateTimeListener constructor.
     *
     * @param ClassContains $classContains
     */
    public function __construct(ClassContains $classContains)
    {
        $this->classContains = $classContains;
    }

    /**
     * @param PreSerializeEvent $preSerializerEvent
     * @throws \ReflectionException
     */
    public function onCoososVersionWorkflowPreSerialize(PreSerializeEvent $preSerializerEvent)
    {
        $model = $preSerializerEvent->getData();
        $this->tranformDateTimeToStringRecursive($model);
    }

    /**
     * @param PreDeserializeEvent $preDeserializeEvent
     * @throws \ReflectionException
     */
    public function onCoososVersionWorkflowPreDeserialize(PreDeserializeEvent $preDeserializeEvent)
    {
        $data = $preDeserializeEvent->getData();
        $data = $this->transformStringToDateTimeRecursive($data);
        $preDeserializeEvent->setData($data);
    }

    /**
     * @param $data
     * @return object
     * @throws \ReflectionException
     */
    public function transformStringToDateTimeRecursive($data)
    {
        if (isset($data['__class_name']) && $data['__class_name'] === 'DateTime') {
            $reflector = new \ReflectionClass(\DateTime::class);
            return $reflector->newInstanceArgs($data['__construct']);
        }

        if (is_array($data)) {
            foreach ($data as $attr => $value) {
                if (is_array($value) && isset($value['__class_name'])) {
                    $data[$attr] = $this->transformStringToDateTimeRecursive($value);
                } elseif (is_array($value) && !isset($value['__class_name'])) {
                    foreach ($value as $key => $element) {
                        $value[$key] = $this->transformStringToDateTimeRecursive($element);
                    }

                    $data[$attr] = $value;
                }
            }
        }

        return $data;
    }

    /**
     * @param mixed $model
     * @return mixed
     * @throws \ReflectionException
     */
    public function tranformDateTimeToStringRecursive($model)
    {
        if (!is_object($model)) {
            return $model;
        }

        if ($this->isDateTime($model)) {
            return ['__class_name' => 'DateTime', '__construct' => [$model->format(\DateTime::ISO8601)]];
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
                    $getterValueArray[$key] = $this->tranformDateTimeToStringRecursive($value);
                }

                $model->{$setterMethod}($getterValueArray);
            } else {
                $model->{$setterMethod}($this->tranformDateTimeToStringRecursive($getterValue));
            }
        }

        return $model;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    protected function isDateTime($value)
    {
        return is_object($value) && get_class($value) === 'DateTime';
    }
}
