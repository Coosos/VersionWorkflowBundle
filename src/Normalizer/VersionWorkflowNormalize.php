<?php

namespace Coosos\VersionWorkflowBundle\Normalizer;

use Coosos\VersionWorkflowBundle\Model\VersionWorkflowTrait;
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
     * {@inheritdoc}
     * @var VersionWorkflowTrait $object
     */
    public function normalize($object, $format = null, array $context = [])
    {
        if (method_exists($object, 'setVersionWorkflow')) {
            $object->setVersionWorkflow(null);
        }

        $parent = parent::normalize($object, $format, $context);
        $parent['__class_name'] = get_class($object);

        return $parent;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
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
                        $data[$attribute] = $this->denormalize($value, $value['__class_name'], $format, $context);
                    }
                } elseif (is_array($value) && !isset($value['__class_name'])) {
                    $arrayCopy = $value;
                    foreach ($arrayCopy as $key => $arrayCopyValue) {
                        if (is_array($arrayCopyValue) && isset($arrayCopyValue['__class_name'])) {
                            $arrayCopy[$key] = $this->denormalize($arrayCopyValue, $arrayCopyValue['__class_name'], $format, $context);
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
