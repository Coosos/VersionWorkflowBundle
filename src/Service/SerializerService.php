<?php

namespace Coosos\VersionWorkflowBundle\Service;

use Coosos\VersionWorkflowBundle\Event\PreSerializerEvent;
use Coosos\VersionWorkflowBundle\Normalizer\VersionWorkflowNormalize;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
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
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * SerializerService constructor.
     *
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize($data, $format, array $context = [])
    {
        $preSerializerEvent = new PreSerializerEvent($data);

        $this->eventDispatcher->dispatch(PreSerializerEvent::EVENT_NAME, $preSerializerEvent);

        return $this->getSerializer()->serialize($data, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function deserialize($data, $type, $format, array $context = [])
    {
        return $this->getSerializer()->deserialize($data, $type, $format, $context = []);
    }

    /**
     * @return \Symfony\Component\Serializer\Serializer
     */
    protected function getSerializer()
    {
        return new \Symfony\Component\Serializer\Serializer(
            [new VersionWorkflowNormalize(), new ObjectNormalizer()],
            [new JsonEncoder()]
        );
    }
}
