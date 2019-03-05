<?php

namespace Coosos\VersionWorkflowBundle\Service;

use Coosos\VersionWorkflowBundle\Event\PostDeserializeEvent;
use Coosos\VersionWorkflowBundle\Event\PreDeserializeEvent;
use Coosos\VersionWorkflowBundle\Event\PreSerializeEvent;
use Coosos\VersionWorkflowBundle\Model\VersionWorkflowTrait;
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
        $preSerializeEvent = new PreSerializeEvent($data);
        $this->eventDispatcher->dispatch(PreSerializeEvent::EVENT_NAME, $preSerializeEvent);

        return $this->getSerializer()->serialize($data, $format, $context);
    }

    /**
     * {@inheritdoc}
     * @return VersionWorkflowTrait
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function deserialize($data, $type, $format, array $context = [])
    {
        $data = $this->getSerializer()->decode($data, $format, $context);

        $preDeserializeEvent = new PreDeserializeEvent($data, $type);
        $this->eventDispatcher->dispatch(PreDeserializeEvent::EVENT_NAME, $preDeserializeEvent);

        $data = $preDeserializeEvent->getData();

        $denormalize = $this->getSerializer()->denormalize($data, $type, $format, $context = []);

        $postDeserializeEvent = new PostDeserializeEvent($denormalize, $data);
        $this->eventDispatcher->dispatch(PostDeserializeEvent::EVENT_NAME, $postDeserializeEvent);

        return $postDeserializeEvent->getData();
    }

    /**
     * @return \Symfony\Component\Serializer\Serializer
     */
    protected function getSerializer()
    {
        return new \Symfony\Component\Serializer\Serializer(
            [new VersionWorkflowNormalize($this->eventDispatcher), new ObjectNormalizer()],
            [new JsonEncoder()]
        );
    }
}
