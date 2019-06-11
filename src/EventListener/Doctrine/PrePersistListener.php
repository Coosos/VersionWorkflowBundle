<?php

namespace Coosos\VersionWorkflowBundle\EventListener\Doctrine;

use Coosos\VersionWorkflowBundle\Annotation\IgnoreChange;
use Coosos\VersionWorkflowBundle\Annotation\OnlyId;
use Coosos\VersionWorkflowBundle\Entity\VersionWorkflow;
use Coosos\VersionWorkflowBundle\Model\VersionWorkflowConfiguration;
use Coosos\VersionWorkflowBundle\Model\VersionWorkflowTrait;
use Coosos\VersionWorkflowBundle\Utils\ClassContains;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\ORMException;
use ReflectionException;
use ReflectionProperty;

/**
 * Class PrePersistListener
 *
 * @package Coosos\VersionWorkflowBundle\EventListener\Doctrine
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
class PrePersistListener
{
    /**
     * @var VersionWorkflowConfiguration
     */
    protected $versionWorkflowConfiguration;

    /**
     * @var ClassContains
     */
    protected $classContains;

    /**
     * @var Reader
     */
    protected $annotationReader;

    /**
     * @var array
     */
    protected $originalObjectByModelHash;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * PreFlushListener constructor.
     *
     * @param VersionWorkflowConfiguration $versionWorkflowConfiguration
     * @param ClassContains                $classContains
     * @param Reader                       $annotationReader
     * @param EntityManagerInterface       $entityManager
     */
    public function __construct(
        VersionWorkflowConfiguration $versionWorkflowConfiguration,
        ClassContains $classContains,
        Reader $annotationReader,
        EntityManagerInterface $entityManager
    ) {
        $this->versionWorkflowConfiguration = $versionWorkflowConfiguration;
        $this->classContains = $classContains;
        $this->annotationReader = $annotationReader;
        $this->originalObjectByModelHash = [];
        $this->entityManager = $entityManager;
    }

    /**
     * @param LifecycleEventArgs $args
     *
     * @return $this
     * @throws ORMException
     * @throws ReflectionException
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        $insert = $args->getEntity();

        if (!$insert instanceof VersionWorkflow) {
            return $this;
        }

        if ($this->versionWorkflowConfiguration->isAutoMerge($insert->getWorkflowName(), $insert->getMarking())) {
            /** @var VersionWorkflowTrait $originalObject */
            $originalObject = $insert->getOriginalObject();
            if ($originalObject->isVersionWorkflowFakeEntity()) {
                $original = $this->linkFakeModelToDoctrineRecursive($args->getEntityManager(), $originalObject);
                $original->setVersionWorkflow($insert);
                $original->setVersionWorkflowFakeEntity(false);

                $args->getEntityManager()->persist($original);
            } else {
                $args->getEntityManager()->persist($insert->getOriginalObject());
            }
        }

        $insert->setOriginalObject(null);

        return $this;
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param VersionWorkflowTrait   $model
     * @param array                  $annotations
     *
     * @return VersionWorkflowTrait|object|null
     * @throws ReflectionException
     */
    protected function linkFakeModelToDoctrineRecursive(EntityManagerInterface $entityManager, $model, $annotations = [])
    {
        if (is_null($model)) {
            return $model;
        }

        if (in_array(spl_object_hash($model), array_keys($this->originalObjectByModelHash))) {
            return $this->originalObjectByModelHash[spl_object_hash($model)];
        }

        $classMetadata = $entityManager->getClassMetadata(get_class($model));
        $identifier = $this->getIdentifiers($classMetadata, $model);

        $originalEntity = $entityManager->getRepository($classMetadata->getName())->findOneBy($identifier);
        $originalEntity = $originalEntity ?? clone $model;

        $this->originalObjectByModelHash[spl_object_hash($model)] = $originalEntity;

        if (!empty($annotations) && isset($annotations['onlyId']) && $annotations['onlyId']) {
            return $originalEntity;
        }

        $this->updateSimpleMapping($classMetadata, $identifier, $originalEntity, $model);
        $this->updateRelationMapping($entityManager, $originalEntity, $model);

        return $originalEntity;
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param mixed                  $originalEntity
     * @param mixed                  $model
     *
     * @throws ReflectionException
     */
    protected function updateRelationMapping($entityManager, $originalEntity, $model)
    {
        $classMetadata = $entityManager->getClassMetadata(get_class($model));

        foreach ($classMetadata->getAssociationMappings() as $metadataField => $associationMapping) {
            $annotationsResults = $this->getAnnotationResults(get_class($originalEntity), $metadataField);

            if (isset($annotationsResults['ignoreChange']) && $annotationsResults['ignoreChange']) {
                continue;
            }

            $parseSingle = $this->parseSingleRelation(
                $originalEntity,
                $model,
                $entityManager,
                $metadataField,
                $associationMapping
            );

            if ($parseSingle) {
                continue;
            }

            $parseList = $this->parseListRelation(
                $originalEntity,
                $model,
                $entityManager,
                $metadataField,
                $classMetadata
            );

            if ($parseList) {
                continue;
            }
        }
    }

    /**
     * @param mixed                  $originalEntity
     * @param mixed                  $model
     * @param EntityManagerInterface $entityManager
     * @param string                 $metadataField
     * @param ClassMetadata          $classMetadata
     *
     * @return bool
     * @throws ReflectionException
     */
    protected function parseListRelation(
        $originalEntity,
        $model,
        $entityManager,
        $metadataField,
        $classMetadata
    ) {
        $compare = $this->compareRelationList($originalEntity, $model, $metadataField, $classMetadata);

        $this->parseRemoveElementFromList($originalEntity, $compare, $classMetadata, $metadataField);

        $this->parseUpdateElementFromList(
            $originalEntity,
            $model,
            $compare,
            $entityManager,
            $classMetadata,
            $metadataField
        );

        $this->parseAddElementFromList($originalEntity, $compare, $entityManager, $metadataField);

        return true;
    }

    /**
     * @param $originalEntity
     * @param $model
     * @param $compare
     * @param $entityManager
     * @param $classMetadata
     * @param $field
     *
     * @throws ReflectionException
     */
    protected function parseUpdateElementFromList(
        $originalEntity,
        $model,
        $compare,
        $entityManager,
        $classMetadata,
        $field
    ) {
        if (!empty($compare['updated'])) {
            $getterMethod = $this->classContains->getGetterMethod($originalEntity, $field);
            $setterMethod = $this->classContains->getSetterMethod($originalEntity, $field);
            $annotationsResults = $this->getAnnotationResults(get_class($originalEntity), $field);

            $modelEntityList = $model->{$getterMethod}();
            $originalEntityList = $originalEntity->{$getterMethod}();

            foreach ($modelEntityList as $key => $item) {
                foreach ($originalEntityList as $itemOriginal) {
                    if ($this->compareIdentifierModel($classMetadata, $item, $itemOriginal)) {
                        $originalEntityList[$key] = $this->linkFakeModelToDoctrineRecursive(
                            $entityManager,
                            $item,
                            $annotationsResults
                        );

                        break;
                    }
                }
            }

            $originalEntity->{$setterMethod}($originalEntityList);
        }
    }

    /**
     * @param mixed                  $originalEntity
     * @param array                  $compare
     * @param EntityManagerInterface $entityManager
     * @param string                 $field
     *
     * @throws ReflectionException
     */
    protected function parseAddElementFromList($originalEntity, $compare, $entityManager, $field)
    {
        if (!empty($compare['added'])) {
            $getterMethod = $this->classContains->getGetterMethod($originalEntity, $field);
            $setterMethod = $this->classContains->getSetterMethod($originalEntity, $field);
            $annotationsResults = $this->getAnnotationResults(get_class($originalEntity), $field);

            $originalEntityList = $originalEntity->{$getterMethod}();
            foreach ($compare['added'] as $key => $item) {
                $originalEntityList[$key] = $this->linkFakeModelToDoctrineRecursive(
                    $entityManager,
                    $item,
                    $annotationsResults
                );
            }

            $originalEntity->{$setterMethod}($originalEntityList);
        }
    }

    /**
     * @param mixed         $originalEntity
     * @param array         $compare
     * @param ClassMetadata $classMetadata
     * @param string        $field
     */
    protected function parseRemoveElementFromList($originalEntity, $compare, $classMetadata, $field)
    {
        if (!empty($compare['removed'])) {
            $getterMethod = $this->classContains->getGetterMethod($originalEntity, $field);

            $list = $originalEntity->{$getterMethod}();
            foreach ($list as $key => $item) {
                foreach ($compare['removed'] as $identifiers) {
                    if ($identifiers == $this->getIdentifiers($classMetadata, $item)) {
                        if ($list instanceof Collection) {
                            $originalEntity->{$getterMethod}()->removeElement($item);

                            $metadata = $this->entityManager->getClassMetadata(get_class($item));
                            $relationToOriginal = $metadata->getAssociationsByTargetClass(get_class($originalEntity));
                            if (!empty($relationToOriginal)) {
                                $relationSetter = $this->classContains->getSetterMethod(
                                    $item,
                                    array_keys($relationToOriginal)[0]
                                );

                                $item->{$relationSetter}(null);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @param $originalEntity
     * @param $model
     * @param $entityManager
     * @param $metadataField
     * @param $associationMapping
     *
     * @return bool
     * @throws ReflectionException
     */
    protected function parseSingleRelation(
        $originalEntity,
        $model,
        $entityManager,
        $metadataField,
        $associationMapping
    ) {
        $getterMethod = $this->classContains->getGetterMethod($originalEntity, $metadataField);
        $setterMethod = $this->classContains->getSetterMethod($originalEntity, $metadataField);
        $annotationsResults = $this->getAnnotationResults(get_class($originalEntity), $metadataField);

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

            return true;
        }

        return false;
    }

    /**
     * @param mixed         $originalEntity
     * @param mixed         $model
     * @param string        $metadataField
     * @param ClassMetadata $classMetadata
     *
     * @return array
     */
    protected function compareRelationList($originalEntity, $model, $metadataField, $classMetadata)
    {
        $results = [
            'added' => [],
            'updated' => [],
            'removed' => [],
        ];

        $getterMethod = $this->classContains->getGetterMethod($originalEntity, $metadataField);

        foreach ($model->{$getterMethod}() as $modelKey => $subModel) {
            $exist = false;
            foreach ($originalEntity->{$getterMethod}() as $subOriginalModel) {
                if ($this->compareIdentifierModel($classMetadata, $subModel, $subOriginalModel)) {
                    $exist = true;

                    $results['updated'][$modelKey] = $this->getIdentifiers($classMetadata, $subModel);

                    break;
                }
            }

            if (!$exist) {
                $results['added'][$modelKey] = $subModel;
            }
        }

        foreach ($originalEntity->{$getterMethod}() as $subOriginalModel) {
            $exist = false;
            foreach ($results['updated'] as $result) {
                if ($result == $this->getIdentifiers($classMetadata, $subOriginalModel)) {
                    $exist = true;

                    break;
                }
            }

            if (!$exist) {
                $results['removed'][] = $this->getIdentifiers($classMetadata, $subOriginalModel);
            }
        }

        return $results;
    }

    /**
     * @param $classMetadata
     * @param $firstObject
     * @param $secondObject
     *
     * @return bool
     */
    protected function compareIdentifierModel($classMetadata, $firstObject, $secondObject)
    {
        $firstObjectIdentifier = $this->getIdentifiers($classMetadata, $firstObject);
        $secondObjectIdentifier = $this->getIdentifiers($classMetadata, $secondObject);

        return $firstObjectIdentifier == $secondObjectIdentifier;
    }

    /**
     * @param $entityClass
     * @param $field
     *
     * @return array
     * @throws ReflectionException
     */
    protected function getAnnotationResults($entityClass, $field)
    {
        $reflectionProperty = new ReflectionProperty($entityClass, $field);
        $onlyId = $this->annotationReader->getPropertyAnnotation($reflectionProperty, OnlyId::class);
        $ignoreChange = $this->annotationReader->getPropertyAnnotation($reflectionProperty, IgnoreChange::class);

        return [
            'onlyId' => !is_null($onlyId),
            'ignoreChange' => !is_null($ignoreChange),
        ];
    }

    /**
     * @param ClassMetadata $classMetadata
     * @param array         $identifiers
     * @param mixed         $originalEntity
     * @param mixed         $model
     *
     * @throws ReflectionException
     */
    protected function updateSimpleMapping($classMetadata, $identifiers, $originalEntity, $model)
    {
        $metadataFields = array_filter($classMetadata->getFieldNames(), function ($val) use ($identifiers) {
            return !in_array($val, array_keys($identifiers));
        });

        foreach ($metadataFields as $metadataField) {
            $getterMethod = $this->classContains->getGetterMethod($originalEntity, $metadataField);
            $setterMethod = $this->classContains->getSetterMethod($originalEntity, $metadataField);
            $annotations = $this->getAnnotationResults(get_class($originalEntity), $metadataField);

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
     *
     * @return array
     */
    protected function getIdentifiers($classMetadata, $model)
    {
        return $this->classContains->getValueByArrayAttribute($model, $classMetadata->getIdentifier());
    }
}
