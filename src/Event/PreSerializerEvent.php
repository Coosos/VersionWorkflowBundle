<?php

namespace Coosos\VersionWorkflowBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class PreSerializerEvent
 *
 * @package Coosos\VersionWorkflowBundle\Event
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
class PreSerializerEvent extends Event
{
    const EVENT_NAME = 'coosos.version_workflow.pre_serializer';

    /**
     * @var mixed
     */
    private $data;

    /**
     * PreSerializerEvent constructor.
     *
     * @param mixed $data
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }
}
