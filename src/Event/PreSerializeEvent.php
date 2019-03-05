<?php

namespace Coosos\VersionWorkflowBundle\Event;

use Coosos\VersionWorkflowBundle\Model\VersionWorkflowTrait;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class PreSerializeEvent
 *
 * @package Coosos\VersionWorkflowBundle\Event
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
class PreSerializeEvent extends Event
{
    const EVENT_NAME = 'coosos.version_workflow.pre_serialize';

    /**
     * @var mixed
     */
    private $data;

    /**
     * @var VersionWorkflowTrait|mixed
     */
    private $object;

    /**
     * PreSerializerEvent constructor.
     *
     * @param mixed $data
     */
    public function __construct($data)
    {
        $this->data = $data;
        $this->object = $data;
    }

    /**
     * @return VersionWorkflowTrait|mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return VersionWorkflowTrait|mixed
     * @deprecated
     */
    public function getObject()
    {
        return $this->object;
    }
}
