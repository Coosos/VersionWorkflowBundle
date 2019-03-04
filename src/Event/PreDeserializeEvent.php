<?php

namespace Coosos\VersionWorkflowBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class PreDeserializeEvent
 *
 * @package Coosos\VersionWorkflowBundle\Event
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
class PreDeserializeEvent extends Event
{
    const EVENT_NAME = 'coosos.version_workflow.pre_deserialize';

    /**
     * @var mixed
     */
    private $data;

    /**
     * @var string
     */
    private $className;

    /**
     * PreSerializerEvent constructor.
     *
     * @param mixed  $data
     * @param string $className
     */
    public function __construct($data, $className)
    {
        $this->data = $data;
        $this->className = $className;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     * @return PreDeserializeEvent
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return string
     */
    public function getClassName(): string
    {
        return $this->className;
    }
}
