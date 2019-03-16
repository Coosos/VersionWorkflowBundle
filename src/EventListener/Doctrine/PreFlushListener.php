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
 * @package Coosos\VersionWorkflowBundle\EventListener\Doctrine
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
class PreFlushListener
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
     * PreFlushListener constructor.
     *
     * @param VersionWorkflowConfiguration $versionWorkflowConfiguration
     * @param ClassContains                $classContains
     * @param Reader                       $annotationReader
     */
    public function __construct(
        VersionWorkflowConfiguration $versionWorkflowConfiguration,
        ClassContains $classContains,
        Reader $annotationReader
    ) {
        $this->versionWorkflowConfiguration = $versionWorkflowConfiguration;
        $this->classContains = $classContains;
        $this->annotationReader = $annotationReader;
        $this->originalObjectByModelHash = [];
    }

    /**
     * @param PreFlushEventArgs $args
     *
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
                    $t = $this->linkFakeModelToDoctrineRecursive($args->getEntityManager(), $originalObject);
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
     *
     * @return object|null
     * @throws \ReflectionException
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
        $originalEntity = $originalEntity ?? $model;

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
     * @throws \ReflectionException
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
                $associationMapping,
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
     * @param array                  $associationMapping
     * @param ClassMetadata          $classMetadata
     *
     * @return bool
     * @throws \ReflectionException
     */
    protected function parseListRelation(
        $originalEntity,
        $model,
        $entityManager,
        $metadataField,
        $associationMapping,
        $classMetadata
    ) {
        $getterMethod = $this->classContains->getGetterMethod($originalEntity, $metadataField);
        $setterMethod = $this->classContains->getSetterMethod($originalEntity, $metadataField);
        $annotationsResults = $this->getAnnotationResults(get_class($originalEntity), $metadataField);

        $compare = $this->compareRelationList($originalEntity, $model, $metadataField, $classMetadata);

        $this->parseRemoveElementFromList($originalEntity, $compare, $classMetadata, $metadataField);
        $this->parseAddElementFromList($originalEntity, $compare, $entityManager, $metadataField);

        dump($this->originalObjectByModelHash);
        dump($originalEntity);
        /**
         * TODO : Use for array
         * TODO : Check insert & updated
         */

        return true;
    }

    /**
     * @param mixed                  $originalEntity
     * @param array                  $compare
     * @param EntityManagerInterface $entityManager
     * @param string                 $field
     *
     * @throws \ReflectionException
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
            $setterMethod = $this->classContains->getSetterMethod($originalEntity, $field);

            $list = $originalEntity->{$getterMethod}();
            foreach ($list as $key => $item) {
                foreach ($compare['removed'] as $identifiers) {
                    if ($identifiers == $this->getIdentifiers($classMetadata, $item)) {
                        unset($list[$key]);
                    }
                }
            }

            $originalEntity->{$setterMethod}($list);
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
     * @throws \ReflectionException
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
     * @param array         $identifiers
     * @param mixed         $originalEntity
     * @param mixed         $model
     *
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
