<?php

namespace Coosos\VersionWorkflowBundle\EventListener\Doctrine;

use Coosos\VersionWorkflowBundle\Entity\VersionWorkflow;
use Coosos\VersionWorkflowBundle\Model\VersionWorkflowTrait;
use Coosos\VersionWorkflowBundle\Utils\ClassContains;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Class LoadClassMetadataListener
 *
 * @package Coosos\VersionWorkflowBundle\EventListener\Doctrine
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
class LoadClassMetadataListener
{
    /**
     * @var ClassContains
     */
    private $classContains;

    /**
     * LoadClassMetadataListener constructor.
     *
     * @param ClassContains $classContains
     */
    public function __construct(ClassContains $classContains)
    {
        $this->classContains = $classContains;
    }

    /**
     * @param LoadClassMetadataEventArgs $eventArgs
     * @throws \ReflectionException
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        $classMetadata = $eventArgs->getClassMetadata();
        $reflClass = $classMetadata->reflClass;

        if ($reflClass === null) {
            return;
        }

        if ($this->classContains->hasTrait($reflClass, VersionWorkflowTrait::class, true)) {
            if (!$classMetadata->hasField('versionWorkflow')) {
                $this->versionWorkflowRelation($classMetadata, 'versionWorkflow', VersionWorkflow::class);
            }
        }
    }

    /**
     * Map relation
     *
     * @param ClassMetadata $classMetadata
     * @param string        $field
     * @param string        $entity
     */
    public function versionWorkflowRelation(ClassMetadata $classMetadata, string $field, string $entity)
    {
        $classMetadata->mapOneToOne([
            "fieldName" => $field,
            "nullable" => true,
            "targetEntity" => $entity,
        ]);
    }
}
