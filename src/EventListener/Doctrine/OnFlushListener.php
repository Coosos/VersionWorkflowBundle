<?php

namespace Coosos\VersionWorkflowBundle\EventListener\Doctrine;

use Coosos\VersionWorkflowBundle\Model\VersionWorkflowConfiguration;
use Coosos\VersionWorkflowBundle\Model\VersionWorkflowTrait;
use Coosos\VersionWorkflowBundle\Service\VersionWorkflowService;
use Coosos\VersionWorkflowBundle\Utils\ClassContains;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\ListenersInvoker;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\PersistentCollection;
use ReflectionException;
use Symfony\Component\Workflow\Registry;

/**
 * Class OnFlushListener
 *
 * @package Coosos\VersionWorkflowBundle\EventListener\Doctrine
 * @author  Remy Lescallier <lescallier1@gmail.com>
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
     * OnFlushListener constructor.
     *
     * @param ClassContains                $classContains
     * @param VersionWorkflowConfiguration $versionWorkflowConfiguration
     * @param Registry                     $registry
     * @param ListenersInvoker             $listenersInvoker
     * @param VersionWorkflowService       $versionWorkflowService
     */
    public function __construct(
        ClassContains $classContains,
        VersionWorkflowConfiguration $versionWorkflowConfiguration,
        Registry $registry,
        ListenersInvoker $listenersInvoker,
        VersionWorkflowService $versionWorkflowService
    ) {
        $this->classContains = $classContains;
        $this->versionWorkflowConfiguration = $versionWorkflowConfiguration;
        $this->registry = $registry;
        $this->detachDeletionsHash = [];
        $this->listenersInvoker = $listenersInvoker;
        $this->versionWorkflowService = $versionWorkflowService;
    }

    /**
     * Detach entity if is vworkflow (not merge)
     *
     * @param OnFlushEventArgs $args
     *
     * @throws ReflectionException
     * @throws ORMException
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

                        if (!$autoMerge || $scheduledEntity->isVersionWorkflowFakeEntity()) {
                            $this->detachRecursive($args, $scheduledEntity);

                            if ($scheduledEntity->getVersionWorkflow()) {
                                $scheduledEntity->getVersionWorkflow()->setObjectSerialized(
                                    $this->versionWorkflowService->cloneAndSerializeObject($scheduledEntity)
                                );

                                $this->invokePreUpdateEvent(
                                    $args->getEntityManager(),
                                    $scheduledEntity->getVersionWorkflow(),
                                    true
                                );
                            }
                        }
                    }
                }
            }
            if ($scheduledType === 'entityDeletions' || $scheduledType === 'collectionDeletions') {
                foreach ($item as $scheduledEntity) {
                    $this->detachDeletionsHash = [];
                    $detachDeletions = $this->checkDetachDeletionsRecursive($args, $scheduledEntity);
                    if ($detachDeletions && !$scheduledEntity instanceof PersistentCollection) {
                        $args->getEntityManager()->persist($scheduledEntity);
                        $this->detachRecursive($args, $scheduledEntity);
                    }

                    if ($detachDeletions && $scheduledEntity instanceof PersistentCollection) {
                        $mapping = $scheduledEntity->getMapping();
                        $mapping['orphanRemoval'] = false;
                        $scheduledEntity->setOwner($scheduledEntity->getOwner(), $mapping);
                    }
                }
            }
        }

        if ($unitOfWork->getScheduledEntityInsertions()) {
            foreach ($unitOfWork->getScheduledEntityInsertions() as $entityInsertion) {
                if (property_exists($entityInsertion, self::PROPERTY_DETACH)
                    && $entityInsertion->{self::PROPERTY_DETACH}) {
                    $args->getEntityManager()->detach($entityInsertion);
                }
            }
        }
    }

    /**
     * Check if relation use object detached
     *
     * @param OnFlushEventArgs           $args
     * @param VersionWorkflowTrait|mixed $entity
     *
     * @return bool
     */
    protected function checkDetachDeletionsRecursive(OnFlushEventArgs $args, $entity)
    {
        if (is_null($entity)) {
            return false;
        }

        if (property_exists($entity, self::PROPERTY_DETACH) && $entity->{self::PROPERTY_DETACH}) {
            return true;
        }

        if (in_array(spl_object_hash($entity), $this->detachDeletionsHash)) {
            return false;
        }

        if ($entity instanceof PersistentCollection) {
            $isDetach = $this->checkDetachDeletionsRecursive($args, $entity->getOwner());
            if ($isDetach) {
                return true;
            }
        }

        $meta = $args->getEntityManager()->getClassMetadata(get_class($entity));
        foreach ($meta->getAssociationMappings() as $fieldName => $associationMapping) {
            if (!$entity->{'get' . ucfirst($fieldName)}() instanceof Collection) {
                $isDetach = $this->checkDetachDeletionsRecursive($args, $entity->{'get' . ucfirst($fieldName)}());
                if ($isDetach) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Recursive detach
     *
     * @param OnFlushEventArgs           $args
     * @param VersionWorkflowTrait|mixed $entity
     */
    protected function detachRecursive(OnFlushEventArgs $args, $entity)
    {
        $entityManager = $args->getEntityManager();
        $entityManager->detach($entity);
        $entity->{self::PROPERTY_DETACH} = true;
        $classMetaData = $entityManager->getClassMetadata(get_class($entity));
        $this->invokePreUpdateEvent($entityManager, $entity);

        foreach ($classMetaData->getAssociationMappings() as $key => $associationMapping) {
            if ($entity->{'get' . ucfirst($key)}() instanceof PersistentCollection) {
                /** @var PersistentCollection $getCollectionMethod */
                $getCollectionMethod = $entity->{'get' . ucfirst($key)}();
                foreach ($getCollectionMethod as $item) {
                    if (property_exists($item, self::PROPERTY_DETACH) && $item->{self::PROPERTY_DETACH}) {
                        continue;
                    }

                    $this->detachRecursive($args, $item);

                    continue;
                }

                /** @var PersistentCollection $tags */
                $mapping = $getCollectionMethod->getMapping();
                $mapping['isOwningSide'] = false;
                $getCollectionMethod->setOwner($entity, $mapping);
            } elseif (!$entity->{'get' . ucfirst($key)}() instanceof ArrayCollection
                && $key !== self::VERSION_WORKFLOW_PROPERTY) {
                $item = $entity->{'get' . ucfirst($key)}();
                if (!is_null($item)) {
                    if (property_exists($item, self::PROPERTY_DETACH) && $item->{self::PROPERTY_DETACH}) {
                        continue;
                    }

                    $this->detachRecursive($args, $item);
                }

                continue;
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
     * @return bool
     */
    protected function hasConfigurationByWorkflowName($model)
    {
        return $this->versionWorkflowConfiguration->hasConfigurationByWorkflowName($model->getWorkflowName());
    }

    /**
     * @param VersionWorkflowTrait $model
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
     * @param VersionWorkflowTrait $model
     * @param string               $place
     * @return bool
     */
    protected function isAutoMerge($model, $place)
    {
        return $this->versionWorkflowConfiguration->isAutoMerge(
            $model->getWorkflowName(),
            $place
        );
    }

    /**
     * Invoke preUpdate doctrine event
     *
     * @param EntityManagerInterface $entityManager
     * @param mixed                  $entity
     * @param bool                   $recompute
     */
    protected function invokePreUpdateEvent($entityManager, $entity, $recompute = false)
    {
        $classMetadata = $entityManager->getClassMetadata(get_class($entity));
        $unitOfWork = $entityManager->getUnitOfWork();

        $preUpdateInvoke = $this->listenersInvoker->getSubscribedSystems($classMetadata, Events::preUpdate);
        $this->listenersInvoker->invoke(
            $classMetadata,
            Events::preUpdate,
            $entity,
            new PreUpdateEventArgs($entity, $entityManager, $unitOfWork->getEntityChangeSet($entity)),
            $preUpdateInvoke
        );

        if ($recompute) {
            $unitOfWork->recomputeSingleEntityChangeSet($classMetadata, $entity);
        }
    }
}
