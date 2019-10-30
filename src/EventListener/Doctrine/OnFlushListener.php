<?php

namespace Coosos\VersionWorkflowBundle\EventListener\Doctrine;

use Coosos\VersionWorkflowBundle\Entity\VersionWorkflow;
use Coosos\VersionWorkflowBundle\Model\VersionWorkflowConfiguration;
use Coosos\VersionWorkflowBundle\Model\VersionWorkflowTrait;
use Coosos\VersionWorkflowBundle\Service\SerializerService;
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
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\ORM\PersistentCollection;
use ReflectionException;
use Symfony\Component\Workflow\Registry;

/**
 * Class OnFlushListener
 *
 * @package Coosos\VersionWorkflowBundle\EventListener\Doctrine
 * @author  Remy Lescallier <lescallier1@gmail.com>
 *
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
     * OnFlushListener constructor.
     *
     * @param ClassContains                $classContains
     * @param VersionWorkflowConfiguration $versionWorkflowConfiguration
     * @param Registry                     $registry
     * @param VersionWorkflowService       $versionWorkflowService
     * @param EntityManagerInterface       $entityManager
     * @param SerializerService          $serializer
     */
    public function __construct(
        ClassContains $classContains,
        VersionWorkflowConfiguration $versionWorkflowConfiguration,
        Registry $registry,
        VersionWorkflowService $versionWorkflowService,
        EntityManagerInterface $entityManager,
        SerializerService $serializer
    ) {
        $this->classContains = $classContains;
        $this->versionWorkflowConfiguration = $versionWorkflowConfiguration;
        $this->registry = $registry;
        $this->detachDeletionsHash = [];
        $this->listenersInvoker = new ListenersInvoker($entityManager);
        $this->versionWorkflowService = $versionWorkflowService;
        $this->serializer = $serializer;
    }

    /**
     * Detach entity if is vworkflow (not merge)
     *
     * @param OnFlushEventArgs $args
     *
     * @throws ReflectionException
     * @throws ORMException
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
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
                                foreach (array_keys($currentPlace) as $status) {
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
                            $this->detachRecursive($args, $scheduledEntity, $scheduledType);
                            $this->updateSerializedObject($scheduledEntity, $args->getEntityManager());
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
                        $this->detachRecursive($args, $scheduledEntity, $scheduledType);
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
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
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

        $this->detachDeletionsHash[] = spl_object_hash($entity);

        if ($entity instanceof PersistentCollection) {
            $isDetach = $this->checkDetachDeletionsRecursive($args, $entity->getOwner());
            if ($isDetach) {
                return true;
            }
        }

        $meta = $args->getEntityManager()->getClassMetadata(get_class($entity));
        foreach (array_keys($meta->getAssociationMappings()) as $fieldName) {
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
     * @param string|null                $scheduledType
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function detachRecursive(OnFlushEventArgs $args, $entity, ?string $scheduledType = null)
    {
        $entityManager = $args->getEntityManager();
        $classMetaData = $entityManager->getClassMetadata(get_class($entity));
        if (!$entity instanceof VersionWorkflow && $scheduledType && $scheduledType === 'entityUpdates') {
            $this->invokePreUpdateEvent($entityManager, $entity);
        }

        $entityManager->detach($entity);
        $entity->{self::PROPERTY_DETACH} = true;

        foreach (array_keys($classMetaData->getAssociationMappings()) as $key) {
            if ($entity->{'get' . ucfirst($key)}() instanceof PersistentCollection) {
                /** @var PersistentCollection $getCollectionMethod */
                $getCollectionMethod = $entity->{'get' . ucfirst($key)}();
                foreach ($getCollectionMethod as $item) {
                    if (property_exists($item, self::PROPERTY_DETACH) && $item->{self::PROPERTY_DETACH}) {
                        continue;
                    }

                    $this->detachRecursive($args, $item, $scheduledType);

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

                    $this->detachRecursive($args, $item, $scheduledType);
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
     *
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

            $this->invokePreUpdateEvent(
                $entityManager,
                $entity->getVersionWorkflow(),
                true
            );
        }

        return $entity;
    }
}
