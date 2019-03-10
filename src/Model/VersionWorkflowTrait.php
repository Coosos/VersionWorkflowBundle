<?php

namespace Coosos\VersionWorkflowBundle\Model;

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
}
