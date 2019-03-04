<?php

namespace Coosos\VersionWorkflowBundle\Normalizer;

use Coosos\VersionWorkflowBundle\Event\PreDeserializeEvent;
use Coosos\VersionWorkflowBundle\Event\PreSerializeEvent;
use Coosos\VersionWorkflowBundle\Model\VersionWorkflowTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorResolverInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

/**
 * Class VersionWorkflowNormalize
 *
 * @package Coosos\VersionWorkflowBundle\Normalizer
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
class VersionWorkflowNormalize extends ObjectNormalizer implements NormalizerInterface
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        ClassMetadataFactoryInterface $classMetadataFactory = null,
        NameConverterInterface $nameConverter = null,
        PropertyAccessorInterface $propertyAccessor = null,
        PropertyTypeExtractorInterface $propertyTypeExtractor = null,
        ClassDiscriminatorResolverInterface $classDiscriminatorResolver = null,
        callable $objectClassResolver = null,
        array $defaultContext = []
    ) {
        parent::__construct(
            $classMetadataFactory,
            $nameConverter,
            $propertyAccessor,
            $propertyTypeExtractor,
            $classDiscriminatorResolver,
            $objectClassResolver,
            $defaultContext
        );

        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     * @var VersionWorkflowTrait $object
     */
    public function normalize($object, $format = null, array $context = [])
    {
        if (method_exists($object, 'setVersionWorkflow')) {
            $object->setVersionWorkflow(null);
        }

        $preSerializeEvent = new PreSerializeEvent($object);
        $this->eventDispatcher->dispatch(PreSerializeEvent::EVENT_NAME, $preSerializeEvent);

        $parent = parent::normalize($object, $format, $context);
        $parent['__class_name'] = get_class($object);

        return $parent;
    }

    /**
     * {@inheritdoc}
     * @throws \ReflectionException
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        $predeserializeEvent = new PreDeserializeEvent($data, $class);
        $this->eventDispatcher->dispatch(PreDeserializeEvent::EVENT_NAME, $predeserializeEvent);

        $data = $predeserializeEvent->getData();

        $object = $this->denormalizeRecursive($data, $class, $format, $context);

        return $object;
    }

    /**
     * @param $data
     * @param $class
     * @param $format
     * @param $context
     * @return object
     * @throws \ReflectionException
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    protected function denormalizeRecursive($data, $class, $format = null, array $context = [])
    {
        if (is_object($data)) {
            return $data;
        }

        if ($class !== \ArrayIterator::class) {
            foreach ($data as $attribute => $value) {
                if (is_array($value) && isset($value['__class_name'])) {
                    if ($value['__class_name'] === 'Doctrine\Common\Collections\ArrayCollection') {
                        $arrayCopy = $value['iterator']['arrayCopy'];
                        foreach ($arrayCopy as $key => $arrayCopyValue) {
                            if (is_array($arrayCopyValue) && isset($arrayCopyValue['__class_name'])) {
                                $arrayCopy[$key] = $this->denormalize($arrayCopyValue, $arrayCopyValue['__class_name'], $format, $context);
                            }
                        }

                        $data[$attribute] = $arrayCopy;
                    } else {
                        $data[$attribute] = $this->denormalizeRecursive($value, $value['__class_name'], $format, $context);
                    }
                } elseif (is_array($value) && !isset($value['__class_name'])) {
                    $arrayCopy = $value;
                    foreach ($arrayCopy as $key => $arrayCopyValue) {
                        if (is_array($arrayCopyValue) && isset($arrayCopyValue['__class_name'])) {
                            $arrayCopy[$key] = $this->denormalizeRecursive($arrayCopyValue, $arrayCopyValue['__class_name'], $format, $context);
                        }
                    }

                    $data[$attribute] = $arrayCopy;
                }
            }
        }

        return parent::denormalize($data, $class, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return parent::supportsDenormalization($data, $type, $format);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return is_object($data) && $format === 'json';
    }
}
