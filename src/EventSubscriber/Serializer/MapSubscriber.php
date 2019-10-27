<?php

namespace Coosos\VersionWorkflowBundle\EventSubscriber\Serializer;

use ArrayAccess;
use Coosos\VersionWorkflowBundle\Model\VersionWorkflowTrait;
use Coosos\VersionWorkflowBundle\Utils\ClassContains;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Context;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\EventDispatcher\PreDeserializeEvent;
use JMS\Serializer\EventDispatcher\PreSerializeEvent;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
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
    const ATTR_DATA_NAME = 'version_workflow_mappings';

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
     * @var mixed|null
     */
    private $currentObject;

    /**
     * @var array
     */
    private $currentMappings;

    /**
     * TransformRelationSubscriber constructor.
     *
     * @param ClassContains $classContains
     */
    public function __construct(ClassContains $classContains)
    {
        $this->classContains = $classContains;
        $this->currentObject = null;
        $this->currentMappings = [];
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
            [
                'event' => 'serializer.post_serialize',
                'method' => 'onPostSerialize',
            ],
            [
                'event' => 'serializer.pre_deserialize',
                'method' => 'onPreDeserialize',
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
        if (is_object($event->getObject())
            && $this->classContains->hasTrait($event->getObject(), VersionWorkflowTrait::class)
            && $event->getContext()->getDepth() === 1
        ) {
            $this->alreadyHashObject = [];
            $this->currentObject = $event->getObject();
            $this->currentMappings = $this->optimizeMappingSerialize(
                $this->buildMap($event->getObject(), $event->getContext())
            );
        }
    }

    /**
     * @param ObjectEvent $event
     */
    public function onPostSerialize(ObjectEvent $event)
    {
        if ($event->getObject() === $this->currentObject) {
            /** @var SerializationVisitorInterface $visitor */
            $visitor = $event->getVisitor();
            $visitor->visitProperty(
                new StaticPropertyMetadata('', self::ATTR_DATA_NAME, $this->currentMappings),
                $this->currentMappings
            );
        }
    }

    /**
     * @param PreDeserializeEvent $event
     */
    public function onPreDeserialize(PreDeserializeEvent $event)
    {
        if ($event->getContext()->getDepth() === 1 && isset($event->getData()[self::ATTR_DATA_NAME])) {
            $this->currentMappings = $event->getData()[self::ATTR_DATA_NAME];
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
        if ($this->classContains->hasTrait($object, VersionWorkflowTrait::class)
            && $event->getContext()->getDepth() === 0
        ) {
            $map = $this->currentMappings;
            $this->parseDeserialize($object, $map);
        }
    }

    /**
     * Build map
     *
     * @param VersionWorkflowTrait $object
     * @param Context              $context
     * @param string|null          $prev
     *
     * @return mixed
     * @throws ReflectionException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function buildMap($object, Context $context, string $prev = null)
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

        $propertyMetadata = $context->getMetadataFactory()->getMetadataForClass(get_class($object))->propertyMetadata;
        $properties = (new ReflectionClass($object))->getProperties(
            ReflectionProperty::IS_PUBLIC |
            ReflectionProperty::IS_PROTECTED |
            ReflectionProperty::IS_PRIVATE
        );

        foreach ($properties as $property) {
            if (!in_array($property->getName(), array_keys($propertyMetadata))) {
                continue;
            }

            $property->setAccessible(true);
            $propertyValue = $property->getValue($object);

            if (is_object($propertyValue) && !$propertyValue instanceof ArrayAccess) {
                $map = array_merge(
                    $map,
                    $this->buildMap($propertyValue, $context, sprintf('%s,%s', $prev, $property->getName()))
                );
            } elseif ((is_array($propertyValue) || $propertyValue instanceof ArrayAccess)
                && $property->getName() !== 'versionWorkflowMap'
            ) {
                foreach ($propertyValue as $key => $item) {
                    $map = array_merge(
                        $map,
                        $this->buildMap($item, $context, sprintf('%s,%s,__array,%s', $prev, $property->getName(), $key))
                    );
                }
            }
        }

        return $map;
    }

    /**
     * Optmize mapping
     *
     * @param array $mappings
     *
     * @return array
     */
    protected function optimizeMappingSerialize(array $mappings): array
    {
        $tempMapping = $newMapping = [];

        $i = 0;
        foreach ($mappings as $path => $mapping) {
            if (!isset($tempMapping[$mapping])) {
                $tempMapping[$mapping] = ++$i;
            }

            $newMapping[$path] = $tempMapping[$mapping];
        }

        return $newMapping;
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
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
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
