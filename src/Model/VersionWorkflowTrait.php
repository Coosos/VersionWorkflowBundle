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
     * @var VersionWorkflow|null
     */
    protected $versionWorkflow;

    /**
     * @return VersionWorkflow|null
     */
    public function getVersionWorkflow()
    {
        return $this->versionWorkflow;
    }

    /**
     * @param VersionWorkflow|null $versionWorkflow
     * @return VersionWorkflowTrait
     */
    public function setVersionWorkflow(?VersionWorkflow $versionWorkflow)
    {
        $this->versionWorkflow = $versionWorkflow;

        return $this;
    }
}
