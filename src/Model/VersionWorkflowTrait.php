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
}
