<?php

namespace Coosos\VersionWorkflowBundle\EventListener\Serializer;

use Coosos\VersionWorkflowBundle\Event\PostDeserializeEvent;
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
    const ATTR_NAME = 'versionworkflow_splobjecthash';

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

        if (property_exists($sourceObject, self::ATTR_NAME)) {
            $data[self::ATTR_NAME] = $sourceObject->{self::ATTR_NAME};
        }

        $postNormalizeEvent->setData($data);
    }

    /**
     * @param PostDeserializeEvent $postDeserializeEvent
     */
    public function onCoososVersionWorkflowPostDeserialize(PostDeserializeEvent $postDeserializeEvent)
    {
        $this->alreadyHashObject = [];
        $data = $postDeserializeEvent->getData();
        $source = $postDeserializeEvent->getSourceData();
        $this->analyzeToRestoreRelation($source, $data);
        $this->restoreRelation($source, $data);

        $postDeserializeEvent->setData($data);
    }

    /**
     * Restore relation in object
     *
     * @param array|mixed $source
     * @param mixed $data
     * @return null
     */
    protected function restoreRelation($source, $data)
    {
        if (!is_array($source) && is_object($source)) {
            return null;
        }

        if (!isset($source['__class_name'])) {
            foreach ($source as $key => $item) {
                $this->restoreRelation($item, $data[$key]);
            }

            return null;
        }

        foreach ($source as $attr => $value) {
            $getterMethod = $this->classContains->getGetterMethod($data, $attr);
            $setterMethod = $this->classContains->getSetterMethod($data, $attr);
            if (is_array($value) && isset($value['__class_name'])) {
                if ($getterMethod) {
                    $this->restoreRelation($value, $data->{$getterMethod}());
                }
            } elseif (is_array($value) && !isset($value['__class_name'])) {
                foreach ($value as $key => $item) {
                    if ($getterMethod) {
                        $this->restoreRelation($item, $data->{$getterMethod}()[$key]);
                    }
                }
            } elseif (is_string($value) && isset($this->alreadyHashObject[$value]) && $attr !== self::ATTR_NAME) {
                $data->{$setterMethod}($this->alreadyHashObject[$value]);
            }
        }

        return null;
    }

    /**
     * Analyze for restore relation
     *
     * @param mixed $source
     * @param mixed $data
     * @return null
     */
    protected function analyzeToRestoreRelation($source, $data)
    {
        if (!is_array($source) && is_object($source)) {
            return null;
        }

        if (!isset($source['__class_name'])) {
            foreach ($source as $key => $item) {
                $this->analyzeToRestoreRelation($item, $data[$key]);
            }

            return null;
        }

        $this->alreadyHashObject[$source[self::ATTR_NAME]] = $data;

        foreach ($source as $attr => $value) {
            $getterMethod = $this->classContains->getGetterMethod($data, $attr);
            if (is_array($value) && isset($value['__class_name'])) {
                if ($getterMethod) {
                    $this->analyzeToRestoreRelation($value, $data->{$getterMethod}());
                }
            } elseif (is_array($value) && !isset($value['__class_name'])) {
                foreach ($value as $key => $item) {
                    if ($getterMethod) {
                        $this->analyzeToRestoreRelation($item, $data->{$getterMethod}()[$key]);
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param array|mixed $source
     * @param mixed $data
     * @return mixed
     */
    protected function transformRelationUnidirectionalToBidirectionalRecursive($source, $data)
    {
        if (is_object($source)) {
            return $source;
        }

        if (isset($source[self::ATTR_NAME])) {
            if (isset($this->alreadyHashObject[$source[self::ATTR_NAME]])) {
                return $this->alreadyHashObject[$source[self::ATTR_NAME]];
            }

            $this->alreadyHashObject[$source[self::ATTR_NAME]] = $data;
        }




        return $data;
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
        $object->{self::ATTR_NAME} = $splObjectHash;

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
