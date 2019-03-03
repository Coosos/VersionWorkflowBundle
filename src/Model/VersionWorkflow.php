<?php

namespace Coosos\VersionWorkflowBundle\Model;

/**
 * Class VersionWorkflow
 *
 * @package Coosos\VersionWorkflowBundle\Model
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
class VersionWorkflow
{
    /**
     * @var VersionWorkflow|null
     */
    private $inherit;

    /**
     * @var int|null
     */
    private $id;

    /**
     * @var string
     */
    private $workflowName;

    /**
     * @var string
     */
    private $modelName;

    /**
     * @var string|null
     */
    private $marking;

    /**
     * @var string|null
     */
    private $objectSerialized;

    /**
     * @var mixed|null
     */
    private $objectDeserialized;

    /**
     * @return VersionWorkflow|null
     */
    public function getInherit(): ?VersionWorkflow
    {
        return $this->inherit;
    }

    /**
     * @param VersionWorkflow|null $inherit
     * @return VersionWorkflow
     */
    public function setInherit(?VersionWorkflow $inherit): VersionWorkflow
    {
        $this->inherit = $inherit;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     * @return VersionWorkflow
     */
    public function setId(?int $id): VersionWorkflow
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getWorkflowName(): string
    {
        return $this->workflowName;
    }

    /**
     * @param string $workflowName
     * @return VersionWorkflow
     */
    public function setWorkflowName(string $workflowName): VersionWorkflow
    {
        $this->workflowName = $workflowName;

        return $this;
    }

    /**
     * @return string
     */
    public function getModelName(): string
    {
        return $this->modelName;
    }

    /**
     * @param string $modelName
     * @return VersionWorkflow
     */
    public function setModelName(string $modelName): VersionWorkflow
    {
        $this->modelName = $modelName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getMarking(): ?string
    {
        return $this->marking;
    }

    /**
     * @param string|null $marking
     * @return VersionWorkflow
     */
    public function setMarking(?string $marking): VersionWorkflow
    {
        $this->marking = $marking;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getObjectSerialized(): ?string
    {
        return $this->objectSerialized;
    }

    /**
     * @param string|null $objectSerialized
     * @return VersionWorkflow
     */
    public function setObjectSerialized(?string $objectSerialized): VersionWorkflow
    {
        $this->objectSerialized = $objectSerialized;

        return $this;
    }

    /**
     * @return mixed|null
     */
    public function getObjectDeserialized()
    {
        return $this->objectDeserialized;
    }

    /**
     * @param mixed|null $objectDeserialized
     * @return VersionWorkflow
     */
    public function setObjectDeserialized($objectDeserialized): VersionWorkflow
    {
        $this->objectDeserialized = $objectDeserialized;

        return $this;
    }
}
