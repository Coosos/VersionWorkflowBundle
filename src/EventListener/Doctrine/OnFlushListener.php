<?php

namespace Coosos\VersionWorkflowBundle\EventListener\Doctrine;

use Coosos\VersionWorkflowBundle\Doctrine\DetachEntity;
use Coosos\VersionWorkflowBundle\Model\VersionWorkflowConfiguration;
use Coosos\VersionWorkflowBundle\Model\VersionWorkflowTrait;
use Coosos\VersionWorkflowBundle\Service\SerializerService;
use Coosos\VersionWorkflowBundle\Service\VersionWorkflowService;
use Coosos\VersionWorkflowBundle\Utils\AutoMergeCheck;
use Coosos\VersionWorkflowBundle\Utils\ClassContains;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\ListenersInvoker;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\ORMInvalidArgumentException;
use ReflectionException;
use Symfony\Component\Workflow\Registry;

/**
 * Class OnFlushListener
 *
 * @package Coosos\VersionWorkflowBundle\EventListener\Doctrine
 * @author  Remy Lescallier <lescallier1@gmail.com>
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OnFlushListener
{
    const PROPERTY_DETACH = 'workflowDetach';

    const VERSION_WORKFLOW_PROPERTY = 'versionWorkflow';

    /**
     * @var ClassContains
     */
    protected $classContains;

    /**
     * @var VersionWorkflowConfiguration
     */
    protected $versionWorkflowConfiguration;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var array
     */
    protected $detachDeletionsHash;

    /**
     * @var ListenersInvoker
     */
    protected $listenersInvoker;

    /**
     * @var VersionWorkflowService
     */
    protected $versionWorkflowService;

    /**
     * @var SerializerService
     */
    protected $serializer;

    /**
     * @var AutoMergeCheck
     */
    protected $autoMergeCheck;

    /**
     * @var DetachEntity
     */
    protected $detachEntity;

    /**
     * OnFlushListener constructor.
     *
     * @param ClassContains                $classContains
     * @param VersionWorkflowConfiguration $versionWorkflowConfiguration
     * @param Registry                     $registry
     * @param VersionWorkflowService       $versionWorkflowService
     * @param EntityManagerInterface       $entityManager
     * @param SerializerService            $serializer
     * @param AutoMergeCheck               $autoMergeCheck
     * @param DetachEntity                 $detachEntity
     */
    public function __construct(
        ClassContains $classContains,
        VersionWorkflowConfiguration $versionWorkflowConfiguration,
        Registry $registry,
        VersionWorkflowService $versionWorkflowService,
        EntityManagerInterface $entityManager,
        SerializerService $serializer,
        AutoMergeCheck $autoMergeCheck,
        DetachEntity $detachEntity
    ) {
        $this->classContains = $classContains;
        $this->versionWorkflowConfiguration = $versionWorkflowConfiguration;
        $this->registry = $registry;
        $this->detachDeletionsHash = [];
        $this->listenersInvoker = new ListenersInvoker($entityManager);
        $this->versionWorkflowService = $versionWorkflowService;
        $this->serializer = $serializer;
        $this->autoMergeCheck = $autoMergeCheck;
        $this->detachEntity = $detachEntity;
    }

    /**
     * Detach entity if is vworkflow (not merge)
     *
     * @param OnFlushEventArgs $args
     *
     * @throws ReflectionException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $entityManager = $args->getEntityManager();
        $unitOfWork = $args->getEntityManager()->getUnitOfWork();
        $entitiesDetached = [];
        $scheduledEntityList = [
            'entityUpdates' => $unitOfWork->getScheduledEntityUpdates(),
            'entityInsertions' => $unitOfWork->getScheduledEntityInsertions(),
            'entityDeletions' => $unitOfWork->getScheduledEntityDeletions(),
        ];

        foreach ($scheduledEntityList as $entityType => $scheduledEntities) {
            foreach ($scheduledEntities as $scheduledEntity) {
                if (!$this->hasVersionWorkflowTrait($scheduledEntity)
                    || empty($scheduledEntity->getWorkflowName())
                    || !$this->hasConfigurationByWorkflowName($scheduledEntity)
                ) {
                    continue;
                }

                if (!$this->isAutoMergeEntity($scheduledEntity) || $scheduledEntity->isVersionWorkflowFakeEntity()) {
                    $preUpdateInvoke = function ($entity) use ($entityType, $entityManager) {
                        if ($entityType === 'entityUpdates') {
                            $this->invokePreUpdateEvent($entityManager, $entity);
                        }
                    };

                    $invokes = ['preUpdate' => $preUpdateInvoke];
                    $this->detachEntity->detach($scheduledEntity, $unitOfWork, $entitiesDetached, $invokes);
                    $this->updateSerializedObject($scheduledEntity, $args->getEntityManager());
                }
            }
        }

        $entityDeletions = $unitOfWork->getScheduledEntityDeletions();
        foreach ([$entityDeletions] as $scheduledEntities) {
            foreach ($scheduledEntities as $scheduledEntity) {
                foreach ($unitOfWork->getEntityChangeSet($scheduledEntity) as $changeSet) {
                    foreach ($changeSet as $value) {
                        if (is_object($value) && isset($entitiesDetached[spl_object_hash($value)])) {
                            $entitiesDetached[spl_object_hash($scheduledEntity)] = true;
                            $this->detachEntity->unsetFromUnitOfWork(
                                $unitOfWork,
                                'entityDeletions',
                                spl_object_hash($scheduledEntity)
                            );
                        }
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
     * @throws ReflectionException
     */
    protected function hasVersionWorkflowTrait($model)
    {
        return $this->classContains->hasTrait($model, VersionWorkflowTrait::class);
    }

    /**
     * @param VersionWorkflowTrait $model
     *
     * @return bool
     */
    protected function hasConfigurationByWorkflowName($model)
    {
        return $this->versionWorkflowConfiguration->hasConfigurationByWorkflowName($model->getWorkflowName());
    }

    /**
     * Check is auto merge by entity
     *
     * @param VersionWorkflowTrait $scheduledEntity
     *
     * @return bool
     */
    protected function isAutoMergeEntity($scheduledEntity)
    {
        return $this->autoMergeCheck->isAutoMergeEntity($scheduledEntity);
    }

    /**
     * Invoke preUpdate doctrine event
     *
     * @param EntityManagerInterface $entityManager
     * @param mixed                  $entity
     * @param bool                   $recompute
     * @SuppressWarnings(PHPMD.EmptyCatchBlock)
     */
    protected function invokePreUpdateEvent($entityManager, $entity, $recompute = false)
    {
        $classMetadata = $entityManager->getClassMetadata(get_class($entity));
        $unitOfWork = $entityManager->getUnitOfWork();

        try {
            // Add try catch for ignore recomputeSingleEntityChangeSet error in preUpdate
            $preUpdateInvoke = $this->listenersInvoker->getSubscribedSystems($classMetadata, Events::preUpdate);
            $this->listenersInvoker->invoke(
                $classMetadata,
                Events::preUpdate,
                $entity,
                new PreUpdateEventArgs($entity, $entityManager, $unitOfWork->getEntityChangeSet($entity)),
                $preUpdateInvoke
            );
        } catch (ORMInvalidArgumentException $e) {
        }

        if ($recompute) {
            $unitOfWork->recomputeSingleEntityChangeSet($classMetadata, $entity);
        }
    }

    /**
     * Update serialized object
     *
     * @param VersionWorkflowTrait   $entity
     * @param EntityManagerInterface $entityManager
     *
     * @return VersionWorkflowTrait
     */
    protected function updateSerializedObject($entity, $entityManager)
    {
        if ($entity->getVersionWorkflow()) {
            $entity->getVersionWorkflow()->setObjectSerialized($this->serializer->serialize($entity));
            $this->invokePreUpdateEvent($entityManager, $entity->getVersionWorkflow(), true);
        }

        return $entity;
    }
}
