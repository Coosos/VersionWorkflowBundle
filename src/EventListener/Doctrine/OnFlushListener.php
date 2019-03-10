<?php

namespace Coosos\VersionWorkflowBundle\EventListener\Doctrine;

use Coosos\VersionWorkflowBundle\Model\VersionWorkflowConfiguration;
use Coosos\VersionWorkflowBundle\Model\VersionWorkflowTrait;
use Coosos\VersionWorkflowBundle\Utils\ClassContains;
use Doctrine\ORM\Event\OnFlushEventArgs;

/**
 * Class OnFlushListener
 *
 * @package Coosos\VersionWorkflowBundle\EventListener\Doctrine
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
class OnFlushListener
{
    /**
     * @var ClassContains
     */
    private $classContains;

    /**
     * @var VersionWorkflowConfiguration
     */
    private $versionWorkflowConfiguration;

    /**
     * OnFlushListener constructor.
     *
     * @param ClassContains $classContains
     * @param VersionWorkflowConfiguration $versionWorkflowConfiguration
     */
    public function __construct(
        ClassContains $classContains,
        VersionWorkflowConfiguration $versionWorkflowConfiguration
    ) {
        $this->classContains = $classContains;
        $this->versionWorkflowConfiguration = $versionWorkflowConfiguration;
    }

    /**
     * @param OnFlushEventArgs $args
     * @throws \ReflectionException
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $unitOfWork = $args->getEntityManager()->getUnitOfWork();
        $entityFlushList = [
            'entityUpdates' => $unitOfWork->getScheduledEntityUpdates(),
            'entityInsertions' => $unitOfWork->getScheduledEntityInsertions(),
            'entityDeletions' => $unitOfWork->getScheduledEntityDeletions(),
            'collectionUpdates' => $unitOfWork->getScheduledCollectionUpdates(),
            'collectionDeletions' => $unitOfWork->getScheduledCollectionDeletions(),
        ];

        foreach ($entityFlushList as $scheduledType => $item) {
            if ($scheduledType === 'entityUpdates' || $scheduledType === 'entityInsertions') {
                /** @var VersionWorkflowTrait $scheduledEntity */
                foreach ($item as $scheduledEntity) {
                    if ($this->hasVersionWorkflowTrait($scheduledEntity)
                        && !is_null($scheduledEntity->getWorkflowName())
                        && $this->hasConfigurationByWorkflowName($scheduledEntity)) {

                    }
                }
            }
        }
    }

    /**
     * Check has version workflow trait
     *
     * @param mixed $model
     *
     * @return bool
     * @throws \ReflectionException
     */
    public function hasVersionWorkflowTrait($model)
    {
        return $this->classContains->hasTrait($model, VersionWorkflowTrait::class);
    }

    /**
     * @param VersionWorkflowTrait $model
     * @return bool
     */
    public function hasConfigurationByWorkflowName($model)
    {
        return $this->versionWorkflowConfiguration->hasConfigurationByWorkflowName($model->getWorkflowName());
    }
}
