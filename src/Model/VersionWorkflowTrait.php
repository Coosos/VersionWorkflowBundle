<?php

namespace Coosos\VersionWorkflowBundle\Model;

use JMS\Serializer\Annotation as Serializer;

/**
 * Trait VersionWorkflowTrait
 *
 * @package Coosos\VersionWorkflowBundle\Model
 */
trait VersionWorkflowTrait
{
    /**
     * @var VersionWorkflowModel|null
     */
    protected $versionWorkflow;

    /**
     * @var string|null
     */
    protected $workflowName;

    /**
     * @var bool
     *
     * @Serializer\Type("bool")
     */
    protected $versionWorkflowFakeEntity = false;

    /**
     * @var array
     *
     * @Serializer\Type("array")
     */
    protected $versionWorkflowMap = [];

    /**
     * @return VersionWorkflowModel|null
     */
    public function getVersionWorkflow()
    {
        return $this->versionWorkflow;
    }

    /**
     * @param VersionWorkflowModel|null $versionWorkflow
     * @return VersionWorkflowTrait
     */
    public function setVersionWorkflow(?VersionWorkflowModel $versionWorkflow)
    {
        $this->versionWorkflow = $versionWorkflow;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getWorkflowName()
    {
        return $this->workflowName;
    }

    /**
     * @param string|null $workflowName
     * @return VersionWorkflowTrait
     */
    public function setWorkflowName($workflowName)
    {
        $this->workflowName = $workflowName;

        return $this;
    }

    /**
     * @return bool
     */
    public function isVersionWorkflowFakeEntity(): bool
    {
        return $this->versionWorkflowFakeEntity;
    }

    /**
     * @param bool $versionWorkflowFakeEntity
     * @return VersionWorkflowTrait
     */
    public function setVersionWorkflowFakeEntity(bool $versionWorkflowFakeEntity)
    {
        $this->versionWorkflowFakeEntity = $versionWorkflowFakeEntity;

        return $this;
    }

    /**
     * @return array
     */
    public function getVersionWorkflowMap(): array
    {
        return $this->versionWorkflowMap;
    }

    /**
     * @param array $versionWorkflowMap
     */
    public function setVersionWorkflowMap(array $versionWorkflowMap): void
    {
        $this->versionWorkflowMap = $versionWorkflowMap;
    }
}
