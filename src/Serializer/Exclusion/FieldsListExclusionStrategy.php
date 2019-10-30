<?php

namespace Coosos\VersionWorkflowBundle\Serializer\Exclusion;

use Coosos\VersionWorkflowBundle\Model\VersionWorkflowTrait;
use Coosos\VersionWorkflowBundle\Utils\ClassContains;
use JMS\Serializer\Context;
use JMS\Serializer\Exclusion\ExclusionStrategyInterface;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;
use ReflectionException;

/**
 * Class FieldsListExclusionStrategy
 *
 * @package Coosos\VersionWorkflowBundle\Serializer\Exclusion
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
class FieldsListExclusionStrategy implements ExclusionStrategyInterface
{
    const IGNORE_FIELDS = ['versionWorkflow', 'versionWorkflowFakeEntity', 'workflowName'];

    /**
     * @var ClassContains
     */
    private $classContains;

    /**
     * FieldsListExclusionStrategy constructor.
     *
     * @param ClassContains $classContains
     */
    public function __construct(ClassContains $classContains)
    {
        $this->classContains = $classContains;
    }

    /**
     * @inheritDoc
     */
    public function shouldSkipClass(ClassMetadata $metadata, Context $context): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     *
     * @throws ReflectionException
     */
    public function shouldSkipProperty(PropertyMetadata $property, Context $context): bool
    {
        if (!$this->classContains->hasTrait($property->class, VersionWorkflowTrait::class)) {
            return false;
        }

        return in_array($property->name, self::IGNORE_FIELDS);
    }
}
