<?php

namespace Coosos\VersionWorkflowBundle\EventListener\Doctrine;

use Coosos\VersionWorkflowBundle\Annotation\OnlyId;
use Coosos\VersionWorkflowBundle\Entity\VersionWorkflow;
use Coosos\VersionWorkflowBundle\Model\VersionWorkflowConfiguration;
use Coosos\VersionWorkflowBundle\Model\VersionWorkflowTrait;
use Coosos\VersionWorkflowBundle\Utils\ClassContains;
use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * Class PreFlushListener
 *
 * @package Coosos\VersionWorkflowBundle\EventListener\Doctrine
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
class PreFlushListener
{
    /**
     * @var VersionWorkflowConfiguration
     */
    private $versionWorkflowConfiguration;
    /**
     * @var ClassContains
     */
    private $classContains;
    /**
     * @var Reader
     */
    private $annotationReader;

    /**
     * PreFlushListener constructor.
     *
     * @param VersionWorkflowConfiguration $versionWorkflowConfiguration
     * @param ClassContains $classContains
     * @param Reader $annotationReader
     */
    public function __construct(
        VersionWorkflowConfiguration $versionWorkflowConfiguration,
        ClassContains $classContains,
        Reader $annotationReader
    ) {
        $this->versionWorkflowConfiguration = $versionWorkflowConfiguration;
        $this->classContains = $classContains;
        $this->annotationReader = $annotationReader;
    }

    /**
     * @param PreFlushEventArgs $args
     * @throws \Doctrine\ORM\ORMException
     * @throws \ReflectionException
     */
    public function preFlush(PreFlushEventArgs $args)
    {
        $inserts = $args->getEntityManager()->getUnitOfWork()->getScheduledEntityInsertions();
        foreach ($inserts as $insert) {
            if (!$insert instanceof VersionWorkflow) {
                continue;
            }

            if ($this->versionWorkflowConfiguration->isAutoMerge($insert->getWorkflowName(), $insert->getMarking())) {
                $args->getEntityManager()->persist($insert->getOriginalObject());

                /** @var VersionWorkflowTrait $originalObject */
                $originalObject = $insert->getOriginalObject();
                if ($originalObject->isVersionWorkflowFakeEntity()) {
                    $this->linkFakeModelToDoctrineRecursive($args->getEntityManager(), $originalObject);
                }
            }

            $insert->setOriginalObject(null);
        }

        dump('OK');
        dump($args->getEntityManager()->getUnitOfWork());
        die;
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param VersionWorkflowTrait   $model
     * @param bool                   $onlyId
     * @return object|null
     * @throws \ReflectionException
     */
    protected function linkFakeModelToDoctrineRecursive(EntityManagerInterface $entityManager, $model, $onlyId = false)
    {
        $classMetadata = $entityManager->getClassMetadata(get_class($model));

        $identifier = [];
        foreach ($classMetadata->getIdentifier() as $identifierMetadata) {
            $getterMethod = $this->classContains->getGetterMethod($model, $identifierMetadata);
            if ($getterMethod) {
                $identifier[$identifierMetadata] = $model->{$getterMethod}();
            }
        }

        $originalEntity = $entityManager->getRepository($classMetadata->getName())->findOneBy($identifier);
        $originalEntity = $originalEntity ?? $model;

        if ($onlyId) {
            return $originalEntity;
        }

        $metadataFields = array_filter($classMetadata->getFieldNames(), function ($val) use ($identifier) {
            return !in_array($val, array_keys($identifier));
        });

        foreach ($metadataFields as $metadataField) {
            $getterMethod = $this->classContains->getGetterMethod($originalEntity, $metadataField);
            $setterMethod = $this->classContains->getSetterMethod($originalEntity, $metadataField);
            if ($getterMethod && $setterMethod) {
                $originalEntity->{$setterMethod}($model->{$getterMethod}());
            }
        }

        foreach ($classMetadata->getAssociationMappings() as $metadataField => $associationMapping) {
            $getterMethod = $this->classContains->getGetterMethod($originalEntity, $metadataField);
            $setterMethod = $this->classContains->getSetterMethod($originalEntity, $metadataField);

            if (($associationMapping['type'] === ClassMetadataInfo::MANY_TO_ONE
                || $associationMapping['type'] === ClassMetadataInfo::ONE_TO_ONE)
                && $setterMethod) {
                $reflectionProperty = new \ReflectionProperty(get_class($originalEntity), $metadataField);
                $onlyId = $this->annotationReader->getPropertyAnnotation($reflectionProperty, OnlyId::class);

                $originalEntity->{$setterMethod}(
                    $this->linkFakeModelToDoctrineRecursive(
                        $entityManager,
                        $model->{$getterMethod}(),
                        !is_null($onlyId)
                    )
                );
            }

            // TODO : For array
        }

        return $originalEntity;
    }
}
