<?php

namespace Coosos\VersionWorkflowBundle\Service;

use Coosos\VersionWorkflowBundle\Serializer\Exclusion\FieldsListExclusionStrategy;
use Coosos\VersionWorkflowBundle\Utils\ClassContains;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;

/**
 * Class SerializerService
 *
 * @package Coosos\VersionWorkflowBundle\Service
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
class SerializerService
{
    const SERIALIZE_FORMAT = 'json';

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var ClassContains
     */
    private $classContains;

    /**
     * SerializerService constructor.
     *
     * @param SerializerInterface $serializer
     * @param ClassContains       $classContains
     */
    public function __construct(SerializerInterface $serializer, ClassContains $classContains)
    {
        $this->serializer = $serializer;
        $this->classContains = $classContains;
    }

    /**
     * Serialize
     *
     * @param mixed  $data
     * @param string $format
     *
     * @return string
     */
    public function serialize($data, $format = self::SERIALIZE_FORMAT)
    {
        $context = (SerializationContext::create())
            ->addExclusionStrategy(new FieldsListExclusionStrategy($this->classContains));

        return $this->serializer->serialize($data, $format, $context);
    }

    /**
     * Deserialize
     *
     * @param string $data
     * @param string $type
     * @param string $format
     *
     * @return array|object
     */
    public function deserialize($data, $type, $format = self::SERIALIZE_FORMAT)
    {
        return $this->serializer->deserialize($data, $type, $format);
    }
}
