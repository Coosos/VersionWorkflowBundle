<?php

namespace Coosos\VersionWorkflowBundle\Model;

/**
 * Class VersionWorkflow
 *
 * @package Coosos\VersionWorkflowBundle\Model
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
class VersionWorkflowModel
{
    /**
     * @var VersionWorkflowModel|null
     */
    protected $inherit;

    /**
     * @var int|null
     */
    protected $id;

    /**
     * @var string
     */
    protected $workflowName;

    /**
     * @var string
     */
    protected $modelName;

    /**
     * @var string|null
     */
    protected $marking;

    /**
     * @var string|null
     */
    protected $objectSerialized;

    /**
     * @var mixed|null
     */
    protected $objectDeserialized;

    /**
     * @var mixed|null
     */
    protected $originalObject;

    /**
     * @return VersionWorkflowModel|null
     */
    public function getInherit(): ?VersionWorkflowModel
    {
        return $this->inherit;
    }

    /**
     * @param VersionWorkflowModel|null $inherit
     * @return VersionWorkflowModel
     */
    public function setInherit(?VersionWorkflowModel $inherit): VersionWorkflowModel
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
     * @return VersionWorkflowModel
     */
    public function setId(?int $id): VersionWorkflowModel
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
     * @return VersionWorkflowModel
     */
    public function setWorkflowName(string $workflowName): VersionWorkflowModel
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
     * @return VersionWorkflowModel
     */
    public function setModelName(string $modelName): VersionWorkflowModel
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
     * @return VersionWorkflowModel
     */
    public function setMarking(?string $marking): VersionWorkflowModel
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
     * @return VersionWorkflowModel
     */
    public function setObjectSerialized(?string $objectSerialized): VersionWorkflowModel
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
     * @return VersionWorkflowModel
     */
    public function setObjectDeserialized($objectDeserialized): VersionWorkflowModel
    {
        $this->objectDeserialized = $objectDeserialized;

        return $this;
    }

    /**
     * @return mixed|null
     */
    public function getOriginalObject()
    {
        return $this->originalObject;
    }

    /**
     * @param mixed|null $originalObject
     * @return VersionWorkflowModel
     */
    public function setOriginalObject($originalObject): VersionWorkflowModel
    {
        $this->originalObject = $originalObject;

        return $this;
    }
}
