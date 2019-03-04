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
    protected $versionWorkflowObject;

    /**
     * @return VersionWorkflow|null
     */
    public function getVersionWorkflowObject()
    {
        return $this->versionWorkflowObject;
    }

    /**
     * @param VersionWorkflow|null $versionWorkflowObject
     * @return VersionWorkflowTrait
     */
    public function setVersionWorkflowObject(?VersionWorkflow $versionWorkflowObject)
    {
        $this->versionWorkflowObject = $versionWorkflowObject;

        return $this;
    }
}
