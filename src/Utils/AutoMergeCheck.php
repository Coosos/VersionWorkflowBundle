<?php

namespace Coosos\VersionWorkflowBundle\Utils;

use Coosos\VersionWorkflowBundle\Model\VersionWorkflowConfiguration;
use Coosos\VersionWorkflowBundle\Model\VersionWorkflowTrait;
use Symfony\Component\Workflow\Registry;

/**
 * Class AutoMergeCheck
 * Helper class for check if entity must be keeping or removed
 *
 * @package Coosos\VersionWorkflowBundle\Utils
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
class AutoMergeCheck
{
    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var ClassContains
     */
    private $classContains;

    /**
     * @var VersionWorkflowConfiguration
     */
    private $versionWorkflowConfiguration;

    /**
     * AutoMergeCheck constructor.
     *
     * @param Registry                     $registry
     * @param ClassContains                $classContains
     * @param VersionWorkflowConfiguration $versionWorkflowConfiguration
     */
    public function __construct(
        Registry $registry,
        ClassContains $classContains,
        VersionWorkflowConfiguration $versionWorkflowConfiguration
    ) {
        $this->registry = $registry;
        $this->classContains = $classContains;
        $this->versionWorkflowConfiguration = $versionWorkflowConfiguration;
    }

    /**
     * Check is auto merge by entity
     *
     * @param VersionWorkflowTrait $scheduledEntity
     *
     * @return bool
     */
    public function isAutoMergeEntity($scheduledEntity)
    {
        if ($currentPlace = $this->getCurrentPlace($scheduledEntity)) {
            if (is_array($currentPlace)) {
                foreach (array_keys($currentPlace) as $status) {
                    if ($this->isAutoMerge($scheduledEntity, $status)) {
                        return true;
                    }
                }
            } elseif (is_string($currentPlace)) {
                if ($this->isAutoMerge($scheduledEntity, $currentPlace)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get current place
     *
     * @param VersionWorkflowTrait $model
     *
     * @return mixed|null
     */
    protected function getCurrentPlace($model)
    {
        $workflow = $this->registry->get($model, $model->getWorkflowName());

        $getterMethod = $this->classContains->getGetterMethod($model, $workflow->getMarkingStore()->getProperty());

        if ($getterMethod) {
            return $model->{$getterMethod}();
        }

        return null;
    }

    /**
     * Check is auto merge by place
     *
     * @param VersionWorkflowTrait $model
     * @param string               $place
     *
     * @return bool
     */
    protected function isAutoMerge($model, $place)
    {
        return $this->versionWorkflowConfiguration->isAutoMerge(
            $model->getWorkflowName(),
            $place
        );
    }
}
