<?php

namespace Coosos\VersionWorkflowBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class PreNormalizeEvent
 *
 * @package Coosos\VersionWorkflowBundle\Event
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
class PreNormalizeEvent extends Event
{
    const EVENT_NAME = 'coosos.version_workflow.pre_normalize';

    /**
     * @var array
     */
    private $data;

    /**
     * SerializeEvent constructor.
     *
     * @param $data
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array $data
     * @return PreNormalizeEvent
     */
    public function setData($data): self
    {
        $this->data = $data;

        return $this;
    }
}
