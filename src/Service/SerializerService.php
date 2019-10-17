<?php

namespace Coosos\VersionWorkflowBundle\Service;

use Coosos\VersionWorkflowBundle\Model\VersionWorkflowTrait;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class SerializerService
 *
 * @package Coosos\VersionWorkflowBundle\Service
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
class SerializerService implements SerializerInterface
{
    /**
     * @var \JMS\Serializer\SerializerInterface
     */
    private $serializer;

    /**
     * SerializerService constructor.
     *
     * @param \JMS\Serializer\SerializerInterface $serializer
     */
    public function __construct(\JMS\Serializer\SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize($data, $format, array $context = [])
    {
        return $this->serializer->serialize($data, $format);
    }

    /**
     * {@inheritdoc}
     *
     * @return VersionWorkflowTrait
     */
    public function deserialize($data, $type, $format, array $context = [])
    {
        return $this->serializer->deserialize($data, $type, $format);
    }
}
