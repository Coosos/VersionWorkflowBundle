<?php

namespace Coosos\VersionWorkflowBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class PostDeserializeEvent
 *
 * @package Coosos\VersionWorkflowBundle\Event
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
class PostDeserializeEvent extends Event
{
    const EVENT_NAME = 'coosos.version_workflow.post_deserialize';

    /**
     * @var mixed
     */
    private $data;

    /**
     * @var array
     */
    private $sourceData;

    /**
     * PostDeserializeEvent constructor.
     *
     * @param mixed $data
     * @param array $sourceData
     */
    public function __construct($data, $sourceData)
    {
        $this->data = $data;
        $this->sourceData = $sourceData;
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
     * @return PostDeserializeEvent
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return array
     */
    public function getSourceData(): array
    {
        return $this->sourceData;
    }
}
