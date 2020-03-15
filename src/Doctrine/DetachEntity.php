<?php

namespace Coosos\VersionWorkflowBundle\Doctrine;

use Coosos\VersionWorkflowBundle\Entity\VersionWorkflow;
use Coosos\VersionWorkflowBundle\Model\VersionWorkflowTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;
use ReflectionClass;
use ReflectionException;

/**
 * Class DetachEntity
 * This class use the reflection for access to UnitOfWork properties.
 * Because from version 2.8, detach method is deprecated
 *
 * @package Coosos\VersionWorkflowBundle\Doctrine
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
class DetachEntity
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * DetachEntity constructor.
     *
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Detach doctrine entity
     *
     * @param VersionWorkflowTrait $entity
     * @param UnitOfWork           $unitOfWork
     * @param array                $entitiesDetached
     * @param array                $invokes
     *
     * @throws ReflectionException
     */
    public function detach($entity, UnitOfWork $unitOfWork, &$entitiesDetached = [], array $invokes = [])
    {
        $visited = [];

        $this->doDetach($entity, $unitOfWork, $visited, $entitiesDetached, $invokes);
    }

    /**
     * Executes a detach operation on the given entity.
     *
     * @param object     $entity
     * @param UnitOfWork $unitOfWork
     * @param array      $visited
     * @param array      $entitiesDetached
     * @param array      $invokes
     * @param boolean    $noCascade if true, don't cascade detach operation.
     *
     * @return void
     * @throws ReflectionException
     */
    private function doDetach(
        $entity,
        UnitOfWork $unitOfWork,
        array &$visited,
        array &$entitiesDetached,
        array $invokes,
        $noCascade = false
    ) {
        if ($entity instanceof VersionWorkflow) {
            return;
        }

        $oid = spl_object_hash($entity);

        if (isset($visited[$oid])) {
            return; // Prevent infinite recursion
        }

        $visited[$oid] = $entity; // mark visited
        switch ($unitOfWork->getEntityState($entity, UnitOfWork::STATE_DETACHED)) {
            case UnitOfWork::STATE_MANAGED:
                if ($unitOfWork->isInIdentityMap($entity)) {
                    $unitOfWork->removeFromIdentityMap($entity);
                }

                if (!$entity instanceof VersionWorkflow &&
                    isset($invokes['preUpdate']) &&
                    is_callable($invokes['preUpdate'])
                ) {
                    $invokes['preUpdate']($entity);
                }

                $entitiesDetached[$oid] = true;
                $this->unsetFromUnitOfWork($unitOfWork, 'entityInsertions', $oid);
                $this->unsetFromUnitOfWork($unitOfWork, 'entityUpdates', $oid);
                $this->unsetFromUnitOfWork($unitOfWork, 'entityDeletions', $oid);
                $this->unsetFromUnitOfWork($unitOfWork, 'entityIdentifiers', $oid);
                $this->unsetFromUnitOfWork($unitOfWork, 'entityStates', $oid);
                $this->unsetFromUnitOfWork($unitOfWork, 'originalEntityData', $oid);

                break;
            case UnitOfWork::STATE_NEW:
            case UnitOfWork::STATE_DETACHED:
                return;
        }

        if (!$noCascade) {
            $this->cascadeDetach($entity, $unitOfWork, $visited, $entitiesDetached, $invokes);
        }
    }

    /**
     * Cascades a detach operation to associated entities.
     *
     * @param object     $entity
     * @param UnitOfWork $unitOfWork
     * @param array      $visited
     * @param array      $entitiesDetached
     * @param array      $invokes
     *
     * @return void
     * @throws ReflectionException
     */
    private function cascadeDetach(
        $entity,
        UnitOfWork
        $unitOfWork,
        array &$visited,
        array &$entitiesDetached,
        array $invokes
    ) {
        $class = $this->em->getClassMetadata(get_class($entity));

        foreach ($class->associationMappings as $assoc) {
            $relatedEntities = $class->reflFields[$assoc['fieldName']]->getValue($entity);
            if ($relatedEntities instanceof PersistentCollection ||
                $relatedEntities instanceof Collection ||
                $relatedEntities instanceof ArrayCollection
            ) {
                $this->unsetFromUnitOfWork($unitOfWork, 'collectionUpdates', spl_object_hash($relatedEntities));
                $this->unsetFromUnitOfWork($unitOfWork, 'visitedCollections', spl_object_hash($relatedEntities));
            }

            if ($relatedEntities instanceof PersistentCollection) {
                $relatedEntities = $relatedEntities->unwrap();
            }

            switch (true) {
                case ($relatedEntities instanceof Collection):
                case (is_array($relatedEntities)):
                    foreach ($relatedEntities as $relatedEntity) {
                        $this->doDetach($relatedEntity, $unitOfWork, $visited, $entitiesDetached, $invokes);
                    }
                    break;
                case ($relatedEntities !== null):
                    $this->doDetach($relatedEntities, $unitOfWork, $visited, $entitiesDetached, $invokes);
                    break;

                default:
                    // Do nothing
            }
        }
    }

    /**
     * Use reflection for unset property data from unit of work class
     *
     * @param UnitOfWork $unitOfWork
     * @param string     $property
     * @param string     $oid
     *
     * @throws ReflectionException
     */
    public function unsetFromUnitOfWork(UnitOfWork $unitOfWork, string $property, string $oid)
    {
        $filterCallback = function ($key) use ($oid) {
            return $key !== $oid;
        };

        $propery = (new ReflectionClass($unitOfWork))->getProperty($property);
        $propery->setAccessible(true);

        $properyValueFiltered = array_filter($propery->getValue($unitOfWork), $filterCallback, ARRAY_FILTER_USE_KEY);
        $propery->setValue($unitOfWork, $properyValueFiltered);
    }
}
