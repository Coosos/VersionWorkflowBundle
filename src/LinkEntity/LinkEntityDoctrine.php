<?php

namespace Coosos\VersionWorkflowBundle\LinkEntity;

use Coosos\VersionWorkflowBundle\Annotation\IgnoreChange;
use Coosos\VersionWorkflowBundle\Annotation\OnlyId;
use Coosos\VersionWorkflowBundle\Entity\VersionWorkflow;
use Coosos\VersionWorkflowBundle\Model\VersionWorkflowTrait;
use Coosos\VersionWorkflowBundle\Utils\ClassContains;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata as ORMClassMetadata;
use Doctrine\ORM\PersistentCollection;
use ReflectionProperty;

/**
 * Class LinkEntityDoctrine
 *
 * @package Coosos\VersionWorkflowBundle\LinkEntity
 * @author  Remy Lescallier <lescallier1@gmail.com>
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class LinkEntityDoctrine
{
    /**
     * @var Reader
     */
    protected $annotationReader;

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var array
     */
    protected $originalObjectByModelHash;
    /**
     * @var ClassContains
     */
    private $classContains;

    /**
     * LinkEntityDoctrine constructor.
     *
     * @param Reader                 $annotationReader
     * @param EntityManagerInterface $entityManager
     * @param ClassContains          $classContains
     */
    public function __construct(
        Reader $annotationReader,
        EntityManagerInterface $entityManager,
        ClassContains $classContains
    ) {
        $this->annotationReader = $annotationReader;
        $this->entityManager = $entityManager;
        $this->originalObjectByModelHash = [];
        $this->classContains = $classContains;
    }

    /**
     * Link fake entity with original entity
     *
     * @param VersionWorkflowTrait $model
     * @param array                $annotations
     * @param ClassMetadata|null   $classMetadata
     *
     * @return VersionWorkflowTrait
     */
    public function linkFakeEntityWithOriginalEntity(
        $model,
        array $annotations = [],
        ClassMetadata $classMetadata = null
    ) {
        if (is_null($model) || $model instanceof VersionWorkflow) {
            return $model;
        } elseif (in_array(spl_object_hash($model), array_keys($this->originalObjectByModelHash))) {
            return $this->originalObjectByModelHash[spl_object_hash($model)];
        }

        if (!$classMetadata) {
            $classMetadata = $this->entityManager->getClassMetadata(get_class($model));
        }

        $identifier = $this->getIdentifiers($classMetadata, $model);
        $originalEntity = $this->getOriginalEntity($classMetadata->getName(), $identifier);
        $originalEntity = $originalEntity ?? clone $model;

        $this->originalObjectByModelHash[spl_object_hash($model)] = $originalEntity;
        if (!empty($annotations) && isset($annotations['onlyId']) && $annotations['onlyId']) {
            return $originalEntity;
        }

        $this->updateSimpleMapping($classMetadata, $identifier, $originalEntity, $model);
        $this->updateRelationMapping($classMetadata, $originalEntity, $model);

        return $originalEntity;
    }

    /**
     * @param ORMClassMetadata $classMetadata
     * @param array            $identifiers
     * @param mixed            $originalEntity
     * @param mixed            $model
     */
    protected function updateSimpleMapping($classMetadata, $identifiers, $originalEntity, $model)
    {
        $metadataFields = array_filter($classMetadata->getFieldNames(), function ($val) use ($identifiers) {
            return !in_array($val, array_keys($identifiers));
        });

        foreach ($metadataFields as $metadataField) {
            $getterMethod = $this->classContains->getGetterMethod($originalEntity, $metadataField);
            $setterMethod = $this->classContains->getSetterMethod($originalEntity, $metadataField);
            $annotations = $this->getAnnotationResults($classMetadata->getReflectionProperty($metadataField));

            if (isset($annotations['ignoreChange']) && $annotations['ignoreChange']) {
                continue;
            }

            if ($getterMethod && $setterMethod) {
                $originalEntity->{$setterMethod}($model->{$getterMethod}());
            }
        }
    }

    /**
     * @param ClassMetadata|ORMClassMetadata $classMetadata
     * @param mixed                          $originalEntity
     * @param mixed                          $model
     */
    protected function updateRelationMapping($classMetadata, $originalEntity, $model)
    {
        foreach ($classMetadata->getAssociationMappings() as $metadataField => $associationMapping) {
            $annotationsResults = $this->getAnnotationResults($classMetadata->getReflectionProperty($metadataField));
            if (isset($annotationsResults['ignoreChange']) && $annotationsResults['ignoreChange']) {
                continue;
            }

            $parseSingle = $this->parseSingleRelation(
                $originalEntity,
                $model,
                $classMetadata,
                $metadataField,
                $associationMapping
            );

            if ($parseSingle) {
                continue;
            }

            $parseList = $this->parseListRelation(
                $originalEntity,
                $model,
                $metadataField,
                $classMetadata
            );

            if ($parseList) {
                continue;
            }
        }
    }

    /**
     * @param $originalEntity
     * @param $model
     * @param ClassMetadata|ORMClassMetadata $classMetadata
     * @param $metadataField
     * @param $associationMapping
     *
     * @return bool
     */
    protected function parseSingleRelation(
        $originalEntity,
        $model,
        $classMetadata,
        $metadataField,
        $associationMapping
    ) {
        $getterMethod = $this->classContains->getGetterMethod($originalEntity, $metadataField);
        $setterMethod = $this->classContains->getSetterMethod($originalEntity, $metadataField);
        $annotationsResults = $this->getAnnotationResults($classMetadata->getReflectionProperty($metadataField));

        if (($associationMapping['type'] === ORMClassMetadata::MANY_TO_ONE
                || $associationMapping['type'] === ORMClassMetadata::ONE_TO_ONE)
            && $setterMethod
        ) {
            $originalEntity->{$setterMethod}(
                $this->linkFakeEntityWithOriginalEntity(
                    $model->{$getterMethod}(),
                    $annotationsResults
                )
            );

            return true;
        }

        return false;
    }

    /**
     * @param mixed                               $originalEntity
     * @param mixed                               $model
     * @param string                              $metadataField
     * @param ClassMetadata|ORMClassMetadata $classMetadata
     *
     * @return bool
     */
    protected function parseListRelation(
        $originalEntity,
        $model,
        $metadataField,
        $classMetadata
    ) {
        $getterMethod = $this->classContains->getGetterMethod($originalEntity, $metadataField);
        $setterMethod = $this->classContains->getSetterMethod($originalEntity, $metadataField);
        if (spl_object_hash($originalEntity->{$getterMethod}()) === spl_object_hash($model->{$getterMethod}())) {
            $originalEntity->{$setterMethod}(clone $originalEntity->{$getterMethod}());
        }

        $compare = $this->compareRelationList($originalEntity, $model, $metadataField, $classMetadata);

        $this->parseRemoveElementFromList($originalEntity, $compare, $classMetadata, $metadataField);

        $this->parseUpdateElementFromList(
            $originalEntity,
            $model,
            $compare,
            $classMetadata,
            $metadataField
        );

        $this->parseKeyChangedFromList($compare, $metadataField, $originalEntity);
        $this->parseAddElementFromList($compare, $metadataField, $originalEntity);

        return true;
    }

    /**
     * @param $originalEntity
     * @param $model
     * @param $compare
     * @param ClassMetadata|ORMClassMetadata $classMetadata
     * @param $field
     */
    protected function parseUpdateElementFromList(
        $originalEntity,
        $model,
        $compare,
        $classMetadata,
        $field
    ) {
        if (!empty($compare['updated'])) {
            $getterMethod = $this->classContains->getGetterMethod($originalEntity, $field);
            $setterMethod = $this->classContains->getSetterMethod($originalEntity, $field);
            $annotationsResults = $this->getAnnotationResults($classMetadata->getReflectionProperty($field));

            $modelEntityList = $model->{$getterMethod}();
            $originalEntityList = $originalEntity->{$getterMethod}();

            foreach ($modelEntityList as $item) {
                foreach ($originalEntityList as $key => $itemOriginal) {
                    if ($this->compareIdentifierModel($classMetadata, $item, $itemOriginal)) {
                        $originalEntityList[$key] = $this->linkFakeEntityWithOriginalEntity(
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
     * Parse add element from list
     *
     * @param array        $compare
     * @param string       $field
     * @param mixed|object $originalEntity
     */
    protected function parseAddElementFromList(array $compare, string $field, $originalEntity)
    {
        if (!empty($compare['added'])) {
            $getterMethod = $this->classContains->getGetterMethod($originalEntity, $field);
            $setterMethod = $this->classContains->getSetterMethod($originalEntity, $field);

            $annotationsResults = $this->getAnnotationResults(
                $this->entityManager->getClassMetadata(get_class($originalEntity))->getReflectionProperty($field)
            );

            $originalEntityList = $originalEntity->{$getterMethod}();
            foreach ($compare['added'] as $key => $item) {
                $originalEntityList[$key] = $this->linkFakeEntityWithOriginalEntity(
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
            foreach ($list as $item) {
                foreach ($compare['removed'] as $identifiers) {
                    if ($identifiers == $this->getIdentifiers($classMetadata, $item)) {
                        if ($list instanceof Collection) {
                            $originalEntity->{$getterMethod}()->removeElement($item);
                        }
                    }
                }
            }
        }
    }

    /**
     * Parse key changed from list
     *
     * @param array        $compare
     * @param string       $field
     * @param mixed|object $entity
     *
     * @return Collection|array
     */
    protected function parseKeyChangedFromList(array $compare, string $field, $entity)
    {
        $getterMethod = $this->classContains->getGetterMethod($entity, $field);
        $originalEntityList = $entity->{$getterMethod}();
        if ($originalEntityList instanceof PersistentCollection) {
            $originalEntityList = $originalEntityList->unwrap();
        }

        if (!empty($compare['keyChanged'])) {
            foreach ($compare['keyChanged'] as $sourceKey => $destinationKey) {
                $originalEntityList[$destinationKey] = $originalEntityList[$sourceKey];
                unset($originalEntityList[$sourceKey]);
            }
        }

        return $originalEntityList;
    }

    /**
     * @param mixed         $originalEntity
     * @param mixed         $model
     * @param string        $metadataField
     * @param ClassMetadata $classMetadata
     *
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function compareRelationList($originalEntity, $model, $metadataField, $classMetadata)
    {
        $results = [
            'keyChanged' => [],
            'added' => [],
            'updated' => [],
            'removed' => [],
        ];

        $getterMethod = $this->classContains->getGetterMethod($originalEntity, $metadataField);

        foreach ($model->{$getterMethod}() as $modelKey => $subModel) {
            $exist = false;
            foreach ($originalEntity->{$getterMethod}() as $originalKey => $subOriginalModel) {
                if ($this->compareIdentifierModel($classMetadata, $subModel, $subOriginalModel, true)) {
                    $exist = true;

                    $results['updated'][$modelKey] = $this->getIdentifiers($classMetadata, $subModel);
                    if ($modelKey !== $originalKey) {
                        $results['keyChanged'][$originalKey] = $modelKey;
                    }

                    break;
                }
            }

            if (!$exist) {
                $results['added'][$modelKey] = $subModel;
            }
        }

        foreach ($originalEntity->{$getterMethod}() as $subOriginalModel) {
            if (!$this->checkIdentifierIsNotNull($this->getIdentifiers($classMetadata, $subOriginalModel))) {
                continue;
            }

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
     * Compare identifier model
     *
     * @param ClassMetadata $classMetadata
     * @param mixed|object  $firstObject
     * @param mixed|object  $secondObject
     * @param bool          $checkIsNotNull
     *
     * @return bool
     */
    protected function compareIdentifierModel(
        ClassMetadata $classMetadata,
        $firstObject,
        $secondObject,
        bool $checkIsNotNull = false
    ) {
        $firstObjectIdentifier = $this->getIdentifiers($classMetadata, $firstObject);
        $secondObjectIdentifier = $this->getIdentifiers($classMetadata, $secondObject);

        if (!$checkIsNotNull) {
            return $firstObjectIdentifier == $secondObjectIdentifier;
        }

        return $this->checkIdentifierIsNotNull($firstObjectIdentifier)
            && $firstObjectIdentifier == $secondObjectIdentifier;
    }

    /**
     * Get annotation results
     *
     * @param ReflectionProperty $reflectionProperty
     *
     * @return array
     */
    protected function getAnnotationResults(ReflectionProperty $reflectionProperty)
    {
        $onlyId = $this->annotationReader->getPropertyAnnotation($reflectionProperty, OnlyId::class);
        $ignoreChange = $this->annotationReader->getPropertyAnnotation($reflectionProperty, IgnoreChange::class);

        return [
            'onlyId' => !is_null($onlyId),
            'ignoreChange' => !is_null($ignoreChange),
        ];
    }

    /**
     * @param ClassMetadata $classMetadata
     * @param mixed                               $model
     *
     * @return array
     */
    protected function getIdentifiers($classMetadata, $model)
    {
        return $this->classContains->getValueByArrayAttribute($model, $classMetadata->getIdentifier());
    }

    /**
     * Get original entity
     *
     * @param string $className
     * @param array  $identifiers
     *
     * @return object|null
     */
    protected function getOriginalEntity(string $className, array $identifiers)
    {
        return $this->entityManager->getRepository($className)->findOneBy($identifiers);
    }

    /**
     * Check if identifier list from pbject is not all null
     *
     * @param array $identifiers
     *
     * @return bool
     */
    protected function checkIdentifierIsNotNull(array $identifiers): bool
    {
        $countNull = 0;
        foreach ($identifiers as $value) {
            if (is_null($value)) {
                $countNull++;
            }
        }

        return count($identifiers) !== $countNull;
    }
}
