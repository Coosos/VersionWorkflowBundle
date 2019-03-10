<?php

namespace Coosos\VersionWorkflowBundle\EventListener\Doctrine;

use Coosos\VersionWorkflowBundle\Model\VersionWorkflowConfiguration;
use Coosos\VersionWorkflowBundle\Model\VersionWorkflowTrait;
use Coosos\VersionWorkflowBundle\Utils\ClassContains;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Symfony\Component\Workflow\Registry;

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
     * @var Registry
     */
    private $registry;

    /**
     * OnFlushListener constructor.
     *
     * @param ClassContains $classContains
     * @param VersionWorkflowConfiguration $versionWorkflowConfiguration
     * @param Registry $registry
     */
    public function __construct(
        ClassContains $classContains,
        VersionWorkflowConfiguration $versionWorkflowConfiguration,
        Registry $registry
    ) {
        $this->classContains = $classContains;
        $this->versionWorkflowConfiguration = $versionWorkflowConfiguration;
        $this->registry = $registry;
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
                        $currentPlace = $this->getCurrentPlace($scheduledEntity);
                        $autoMerge = false;

                        if ($currentPlace) {
                            if (is_array($currentPlace)) {
                                foreach ($currentPlace as $status => $value) {
                                    if ($this->isAutoMerge($scheduledEntity, $status)) {
                                        $autoMerge = true;
                                        break;
                                    }
                                }
                            } elseif (is_string($currentPlace)) {
                                if ($this->isAutoMerge($scheduledEntity, $currentPlace)) {
                                    $autoMerge = true;
                                }
                            }
                        }

                        if (!$autoMerge) {
//                            $this->detachRecursive($args, $scheduledEntity);
                        }

                        dump($autoMerge);
                        dump($this->getCurrentPlace($scheduledEntity));
                        die;
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

    /**
     * @param VersionWorkflowTrait $model
     * @return mixed|null
     */
    public function getCurrentPlace($model)
    {
        $workflow = $this->registry->get($model, $model->getWorkflowName());

        $getterMethod = $this->classContains->getGetterMethod($model, $workflow->getMarkingStore()->getProperty());

        if ($getterMethod) {
            return $model->{$getterMethod}();
        }

        return null;
    }

    /**
     * @param VersionWorkflowTrait $model
     * @return bool
     */
    public function isAutoMerge($model, $place)
    {
        return $this->versionWorkflowConfiguration->isAutoMerge(
            $model->getWorkflowName(),
            $place
        );
    }
}
