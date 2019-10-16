<?php

namespace Coosos\VersionWorkflowBundle\EventSubscriber\Serializer;

use ArrayAccess;
use Coosos\VersionWorkflowBundle\Model\VersionWorkflowTrait;
use Coosos\VersionWorkflowBundle\Utils\ClassContains;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\EventDispatcher\PreSerializeEvent;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

/**
 * Class MapSubscriber
 *
 * @package Coosos\VersionWorkflowBundle\EventSubscriber\Serializer
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
class MapSubscriber implements EventSubscriberInterface
{
    /**
     * @var array
     */
    protected $alreadyHashObject;

    /**
     * @var array
     */
    protected $map;

    /**
     * @var ClassContains
     */
    private $classContains;

    /**
     * TransformRelationSubscriber constructor.
     *
     * @param ClassContains $classContains
     */
    public function __construct(ClassContains $classContains)
    {
        $this->classContains = $classContains;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            [
                'event' => 'serializer.pre_serialize',
                'method' => 'onPreSerialize',
            ],
            [
                'event' => 'serializer.post_deserialize',
                'method' => 'onPostDeserialize',
            ],
        ];
    }

    /**
     * @param PreSerializeEvent $event
     *
     * @throws ReflectionException
     */
    public function onPreSerialize(PreSerializeEvent $event)
    {
        if ($this->classContains->hasTrait($event->getObject(), VersionWorkflowTrait::class)) {
            $this->alreadyHashObject = [];
            $event->getObject()->setVersionWorkflowMap($this->buildMap($event->getObject()));
        }
    }

    /**
     * @param ObjectEvent $event
     *
     * @throws ReflectionException
     */
    public function onPostDeserialize(ObjectEvent $event)
    {
        /** @var VersionWorkflowTrait $object */
        $object = $event->getObject();
        if ($this->classContains->hasTrait($object, VersionWorkflowTrait::class)) {
            $map = $object->getVersionWorkflowMap();
            $this->parseDeserialize($object, $map);
//            $object->setVersionWorkflowMap($map); TODO
        }
    }

    /**
     * Build map
     *
     * @param VersionWorkflowTrait $object
     * @param string|null          $prev
     *
     * @return mixed
     * @throws ReflectionException
     */
    protected function buildMap($object, string $prev = null)
    {
        $splObjectHash = spl_object_hash($object);
        if (isset($this->alreadyHashObject[$splObjectHash])) {
            return [$prev => $splObjectHash];
        }

        $this->alreadyHashObject[$splObjectHash] = true;

        if (!$prev) {
            $prev = 'root';
        }

        $map = [];
        $map[$prev] = $splObjectHash;

        $properties = (new ReflectionClass($object))->getProperties(
            ReflectionProperty::IS_PUBLIC |
            ReflectionProperty::IS_PROTECTED |
            ReflectionProperty::IS_PRIVATE
        );

        foreach ($properties as $property) {
            $property->setAccessible(true);
            $propertyValue = $property->getValue($object);

            if (is_object($propertyValue) && !$propertyValue instanceof ArrayAccess) {
                $map = array_merge(
                    $map,
                    $this->buildMap($propertyValue, sprintf('%s,%s', $prev, $property->getName()))
                );
            } elseif (is_array($propertyValue) || $propertyValue instanceof ArrayAccess) {
                foreach ($propertyValue as $key => $item) {
                    $map = array_merge(
                        $map,
                        $this->buildMap($item, sprintf('%s,%s,__array,%s', $prev, $property->getName(), $key))
                    );
                }
            }
        }

        return $map;
    }


    /**
     * Parse deserialize
     *
     * @param VersionWorkflowTrait|mixed      $object
     * @param array                           $map
     * @param string                          $currentMap
     * @param array                           $already
     *
     * @return mixed
     * @throws ReflectionException
     */
    protected function parseDeserialize(
        $object,
        array &$map,
        string $currentMap = 'root',
        array &$already = []
    ) {
        if (isset($already[$map[$currentMap]])) {
            return $already[$map[$currentMap]];
        }

        $already[$map[$currentMap]] = $object;
        unset($map[$currentMap]);

        $properties = (new ReflectionClass($object))->getProperties(
            ReflectionProperty::IS_PUBLIC |
            ReflectionProperty::IS_PROTECTED |
            ReflectionProperty::IS_PRIVATE
        );

        foreach ($properties as $property) {
            $property->setAccessible(true);
            $propertyValue = $property->getValue($object);
            $propertyName = $property->getName();

            if (is_object($propertyValue) && !$propertyValue instanceof ArrayAccess) {
                $property->setValue(
                    $object,
                    $this->parseDeserialize(
                        $propertyValue,
                        $map,
                        sprintf('%s,%s', $currentMap, $propertyName),
                        $already
                    )
                );

                unset($map[sprintf('%s,%s', $currentMap, $propertyName)]);
            } elseif ((is_array($propertyValue) || $propertyValue instanceof ArrayAccess)
                && $propertyName !== 'versionWorkflowMap'
            ) {
                $list = ($propertyValue instanceof ArrayCollection) ? $propertyValue : [];
                foreach ($propertyValue as $key => $item) {
                    if ($list instanceof ArrayCollection) {
                        $list->set(
                            $key,
                            $this->parseDeserialize(
                                $item,
                                $map,
                                sprintf('%s,%s,__array,%s', $currentMap, $propertyName, $key),
                                $already
                            )
                        );
                    } elseif (is_array($propertyValue)) {
                        $list[$key] = $this->parseDeserialize(
                            $item,
                            $map,
                            sprintf('%s,%s,__array,%s', $currentMap, $propertyName, $key),
                            $already
                        );
                    }
                }

                $property->setValue($object, $list);
            } elseif ($propertyValue === null
                && isset($map[$currentMap . ',' . $propertyName])
                && isset($already[$map[$currentMap . ',' . $propertyName]])
            ) {
                $property->setValue($object, $already[$map[$currentMap . ',' . $propertyName]]);
                unset($map[$currentMap . ',' . $propertyName]);
            }
        }

        return $object;
    }
}
