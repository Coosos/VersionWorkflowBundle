<?php

namespace Coosos\VersionWorkflowBundle\Normalizer;

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
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $parent = parent::normalize($object, $format, $context);
        $parent['__class_name'] = get_class($object);

        return $parent;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
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
