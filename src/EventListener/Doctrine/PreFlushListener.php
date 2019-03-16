<?php

namespace Coosos\VersionWorkflowBundle\EventListener\Doctrine;

use Coosos\VersionWorkflowBundle\Annotation\IgnoreChange;
use Coosos\VersionWorkflowBundle\Annotation\OnlyId;
use Coosos\VersionWorkflowBundle\Entity\VersionWorkflow;
use Coosos\VersionWorkflowBundle\Model\VersionWorkflowConfiguration;
use Coosos\VersionWorkflowBundle\Model\VersionWorkflowTrait;
use Coosos\VersionWorkflowBundle\Utils\ClassContains;
use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
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
                    dump($originalObject);
                    $t = $this->linkFakeModelToDoctrineRecursive($args->getEntityManager(), $originalObject);
                    dump($t);
                }
            }

            $insert->setOriginalObject(null);
        }

        dump('OKdd');
        dump($args->getEntityManager()->getUnitOfWork());
        die;
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param VersionWorkflowTrait   $model
     * @param array                  $annotations
     * @return object|null
     * @throws \ReflectionException
     */
    protected function linkFakeModelToDoctrineRecursive(EntityManagerInterface $entityManager, $model, $annotations = [])
    {
        if (is_null($model)) {
            return $model;
        }

        $classMetadata = $entityManager->getClassMetadata(get_class($model));
        $identifier = $this->getIdentifiers($classMetadata, $model);

        $originalEntity = $entityManager->getRepository($classMetadata->getName())->findOneBy($identifier);
        $originalEntity = $originalEntity ?? $model;

        if (!empty($annotations) && isset($annotations['onlyId']) && $annotations['onlyId']) {
            return $originalEntity;
        }

        $this->updateSimpleMapping($classMetadata, $identifier, $originalEntity, $model);
        $this->updateRelationMapping($entityManager, $originalEntity, $model);

        return $originalEntity;
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param mixed $originalEntity
     * @param mixed $model
     * @throws \ReflectionException
     */
    protected function updateRelationMapping($entityManager, $originalEntity, $model)
    {
        $classMetadata = $entityManager->getClassMetadata(get_class($model));

        foreach ($classMetadata->getAssociationMappings() as $metadataField => $associationMapping) {
            $getterMethod = $this->classContains->getGetterMethod($originalEntity, $metadataField);
            $setterMethod = $this->classContains->getSetterMethod($originalEntity, $metadataField);
            $annotationsResults = $this->getAnnotationResults(get_class($originalEntity), $metadataField);

            if (isset($annotationsResults['ignoreChange']) || $annotationsResults['ignoreChange']) {
                continue;
            }

            if (
                ($associationMapping['type'] === ClassMetadataInfo::MANY_TO_ONE
                    || $associationMapping['type'] === ClassMetadataInfo::ONE_TO_ONE)
                && $setterMethod
            ) {
                $originalEntity->{$setterMethod}(
                    $this->linkFakeModelToDoctrineRecursive(
                        $entityManager,
                        $model->{$getterMethod}(),
                        $annotationsResults
                    )
                );
            }

            /**
             * TODO : Use for array
             * TODO : Check insert & delete
             * TODO : Check ignore change
             */
        }
    }

    /**
     * @param $entityClass
     * @param $field
     * @return array
     * @throws \ReflectionException
     */
    protected function getAnnotationResults($entityClass, $field)
    {
        $reflectionProperty = new \ReflectionProperty($entityClass, $field);
        $onlyId = $this->annotationReader->getPropertyAnnotation($reflectionProperty, OnlyId::class);
        $ignoreChange = $this->annotationReader->getPropertyAnnotation($reflectionProperty, IgnoreChange::class);

        return [
            'onlyId' => !is_null($onlyId),
            'ignoreChange' => !is_null($ignoreChange),
        ];
    }

    /**
     * @param ClassMetadata $classMetadata
     * @param array $identifiers
     * @param mixed $originalEntity
     * @param mixed $model
     * @throws \ReflectionException
     */
    protected function updateSimpleMapping($classMetadata, $identifiers, $originalEntity, $model)
    {
        $metadataFields = array_filter($classMetadata->getFieldNames(), function ($val) use ($identifiers) {
            return !in_array($val, array_keys($identifiers));
        });

        foreach ($metadataFields as $metadataField) {
            $getterMethod = $this->classContains->getGetterMethod($originalEntity, $metadataField);
            $setterMethod = $this->classContains->getSetterMethod($originalEntity, $metadataField);
            $annotations  = $this->getAnnotationResults(get_class($originalEntity), $metadataField);

            if (isset($annotations['ignoreChange']) && $annotations['ignoreChange']) {
                continue;
            }

            if ($getterMethod && $setterMethod) {
                $originalEntity->{$setterMethod}($model->{$getterMethod}());
            }
        }
    }

    /**
     * @param ClassMetadata $classMetadata
     * @param mixed         $model
     * @return array
     */
    protected function getIdentifiers($classMetadata, $model)
    {
        $identifier = [];
        foreach ($classMetadata->getIdentifier() as $identifierMetadata) {
            $getterMethod = $this->classContains->getGetterMethod($model, $identifierMetadata);
            if ($getterMethod) {
                $identifier[$identifierMetadata] = $model->{$getterMethod}();
            }
        }

        return $identifier;
    }
}
