<?php

namespace Coosos\VersionWorkflowBundle\EventListener\Serializer;

use Coosos\VersionWorkflowBundle\Event\PostNormalizeEvent;
use Coosos\VersionWorkflowBundle\Event\PreSerializeEvent;
use Coosos\VersionWorkflowBundle\Model\VersionWorkflowTrait;
use Coosos\VersionWorkflowBundle\Utils\ClassContains;

/**
 * Class TransformRelationBidirectionalListener
 *
 * @package Coosos\VersionWorkflowBundle\EventListener\Serializer
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
class TransformRelationBidirectionalListener
{
    /**
     * @var array
     */
    protected $alreadyHashObject;

    /**
     * @var ClassContains
     */
    private $classContains;

    /**
     * TransformRelationBidirectionalListener constructor.
     *
     * @param ClassContains $classContains
     */
    public function __construct(ClassContains $classContains)
    {
        $this->classContains = $classContains;
    }

    /**
     * @param PreSerializeEvent $preSerializeEvent
     * @throws \ReflectionException
     */
    public function onCoososVersionWorkflowPreSerialize(PreSerializeEvent $preSerializeEvent)
    {
        $this->alreadyHashObject = [];
        $object = $preSerializeEvent->getData();
        $this->transformRelationBidirectionalToUnidirectionalRecursive($object);
    }

    /**
     * @param PostNormalizeEvent $postNormalizeEvent
     */
    public function onCoososVersionWorkflowPostNormalize(PostNormalizeEvent $postNormalizeEvent)
    {
        $sourceObject = $postNormalizeEvent->getSourceData();
        $data = $postNormalizeEvent->getData();

        if (property_exists($sourceObject, 'versionWorkflowSplObjectHash')) {
            $data['versionWorkflowSplObjectHash'] = $sourceObject->{'versionWorkflowSplObjectHash'};
        }

        $postNormalizeEvent->setData($data);
    }

    /**
     * @param VersionWorkflowTrait|mixed $object
     * @return VersionWorkflowTrait|mixed
     * @throws \ReflectionException
     */
    protected function transformRelationBidirectionalToUnidirectionalRecursive($object)
    {
        if (!is_object($object)) {
            return $object;
        }

        $splObjectHash = spl_object_hash($object);

        if (isset($this->alreadyHashObject[$splObjectHash])) {
            return $this->alreadyHashObject[$splObjectHash];
        }

        $this->alreadyHashObject[$splObjectHash] = $splObjectHash;
        $object->versionWorkflowSplObjectHash = $splObjectHash;

        $reflect = new \ReflectionClass($object);
        $properties = $reflect->getProperties(
            \ReflectionProperty::IS_PUBLIC |
            \ReflectionProperty::IS_PROTECTED |
            \ReflectionProperty::IS_PRIVATE
        );

        foreach ($properties as $property) {
            $getterMethod = $this->classContains->getGetterMethod($object, $property->getName());
            $setterMethod = $this->classContains->getSetterMethod($object, $property->getName());

            if (is_null($getterMethod) || is_null($setterMethod)) {
                continue;
            }

            $getterValue = $object->{$getterMethod}();
            if (is_array($getterValue)) {
                $getterValueArray = [];
                foreach ($getterValue as $key => $value) {
                    $getterValueArray[$key] = $this->transformRelationBidirectionalToUnidirectionalRecursive($value);
                }

                $object->{$setterMethod}($getterValueArray);
            } else {
                $object->{$setterMethod}($this->transformRelationBidirectionalToUnidirectionalRecursive($getterValue));
            }
        }

        return $object;
    }
}
