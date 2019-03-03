<?php

namespace Coosos\VersionWorkflowBundle\Service;

use Coosos\VersionWorkflowBundle\Normalizer\VersionWorkflowNormalize;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class SerializerService implements SerializerInterface
{
    /**
     * {@inheritdoc}
     */
    public function serialize($data, $format, array $context = [])
    {
        return $this->getSerializer()->serialize($data, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function deserialize($data, $type, $format, array $context = [])
    {
        return $this->getSerializer()->deserialize($data, $type, $format, $context = []);
    }

    protected function getSerializer()
    {
        return new \Symfony\Component\Serializer\Serializer(
            [new VersionWorkflowNormalize(), new ObjectNormalizer()],
            [new JsonEncoder()]
        );
    }
}
