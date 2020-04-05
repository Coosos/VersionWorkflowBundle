<?php

namespace Coosos\VersionWorkflowBundle\Model;

use Coosos\BidirectionalRelation\Annotations\ExcludeFromMapping;

/**
 * Trait VersionWorkflowTrait
 *
 * @package Coosos\VersionWorkflowBundle\Model
 */
trait VersionWorkflowTrait
{
    /**
     * @var VersionWorkflowModel|null
     *
     * @ExcludeFromMapping()
     */
    protected $versionWorkflow;

    /**
     * @var string|null
     *
     * @ExcludeFromMapping()
     */
    protected $workflowName;

    /**
     * @var bool
     *
     * @ExcludeFromMapping()
     */
    protected $versionWorkflowFakeEntity = false;

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
}
