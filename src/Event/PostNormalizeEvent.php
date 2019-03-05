<?php

namespace Coosos\VersionWorkflowBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class PostNormalizeEvent
 *
 * @package Coosos\VersionWorkflowBundle\Event
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
class PostNormalizeEvent extends Event
{
    const EVENT_NAME = 'coosos.version_workflow.post_normalize';

    /**
     * @var array
     */
    private $data;

    /**
     * @var mixed
     */
    private $sourceData;

    /**
     * SerializeEvent constructor.
     *
     * @param $data
     * @param mixed $sourceData
     */
    public function __construct($data, $sourceData)
    {
        $this->data = $data;
        $this->sourceData = $sourceData;
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
     * @return PostNormalizeEvent
     */
    public function setData($data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSourceData()
    {
        return $this->sourceData;
    }
}
