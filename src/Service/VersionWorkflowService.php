<?php

namespace Coosos\VersionWorkflowBundle\Service;

use Coosos\VersionWorkflowBundle\Entity\VersionWorkflow;
use Coosos\VersionWorkflowBundle\Model\VersionWorkflowModel;
use Coosos\VersionWorkflowBundle\Model\VersionWorkflowTrait;
use Coosos\VersionWorkflowBundle\Utils\ClassContains;
use Symfony\Component\Workflow\Registry;

/**
 * Class VersionWorkflowService
 *
 * @package Coosos\VersionWorkflowBundle\Service
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
class VersionWorkflowService
{
    /**
     * @var SerializerService
     */
    private $serializer;

    /**
     * @var Registry
     */
    private $workflows;

    /**
     * @var ClassContains
     */
    private $classContains;

    /**
     * VersionWorkflowService constructor.
     *
     * @param SerializerService $serializer
     * @param Registry          $workflows
     * @param ClassContains     $classContains
     */
    public function __construct(
        SerializerService $serializer,
        Registry $workflows,
        ClassContains $classContains
    ) {
        $this->workflows = $workflows;
        $this->classContains = $classContains;
        $this->serializer = $serializer;
    }

    /**
     * @param VersionWorkflowTrait|mixed $object
     * @param string|null                $workflowName
     * @param string|null                $transition
     * @param string|null                $marking
     *
     * @return VersionWorkflowTrait|mixed
     */
    public function applyTransition($object, ?string $workflowName = null, $transition = null, $marking = null)
    {
        $workflow = $this->workflows->get($object, $workflowName);

        if (is_null($transition)) {
            $setterMethod = $this->classContains->getSetterMethod(
                $object,
                $this->getMarkingProperty($object, $workflowName)
            );

            if (is_null($marking)) {
                $marking = $workflow->getDefinition()->getInitialPlace();
            }

            if ($setterMethod) {
                $object->{$setterMethod}($marking);
            }

            return $object;
        }

        if ($workflow->can($object, $transition)) {
            $workflow->apply($object, $transition);
        }

        return $object;
    }

    /**
     * Transform model to version workflow model
     *
     * @param VersionWorkflowTrait|object $object
     * @param string|null                 $workflowName
     *
     * @return VersionWorkflowModel
     */
    public function transformToVersionWorkflowModel($object, ?string $workflowName = null)
    {
        $versionWorkflow = new VersionWorkflow();
        $versionWorkflow->setWorkflowName($this->getWorkflowName($object, $workflowName));
        $versionWorkflow->setModelName(get_class($object));
        $versionWorkflow->setMarking($this->getMarkingValue($object, $workflowName));
        $versionWorkflow->setOriginalObject($object);

        if ($object->getVersionWorkflow()) {
            $versionWorkflow->setInherit($object->getVersionWorkflow());
        }

        $versionWorkflow->setObjectSerialized($this->serializer->serialize($object));

        $object->setVersionWorkflow($versionWorkflow);
        $object->setWorkflowName($this->getWorkflowName($object, $workflowName));

        return $versionWorkflow;
    }

    /**
     * Transform VersionWorkflowModel list to deserialized entity
     *
     * @param VersionWorkflowModel[] $versionWorkflows
     *
     * @return array
     */
    public function transformVersionWorkflowListToObject(array $versionWorkflows)
    {
        $objects = [];

        foreach ($versionWorkflows as $versionWorkflow) {
            $objects[] = $this->transformToObject($versionWorkflow);
        }

        return $objects;
    }

    /**
     * Transform VersionWorkflowModel to deserialized entity
     *
     * @param VersionWorkflowModel|object $object
     *
     * @return VersionWorkflowTrait|mixed
     */
    public function transformToObject($object)
    {
        $entity = $this->serializer->deserialize(
            $object->getObjectSerialized(),
            $object->getModelName()
        );

        $entity->setVersionWorkflowFakeEntity(true);
        $entity->setVersionWorkflow($object);

        return $entity;
    }

    /**
     * Apply transition and transform object to version workflow model
     *
     * @param VersionWorkflowModel|VersionWorkflowTrait $object
     * @param string|null                               $workflowName
     * @param string|null                               $transition
     * @param array                                     $params
     *
     * @return VersionWorkflowModel
     */
    public function applyTransitionAndTransformToVersionWorkflow(
        $object,
        ?string $workflowName,
        ?string $transition = null,
        array $params = []
    ) {
        $marking = null;

        if (isset($params['marking'])) {
            $marking = $params['marking'];

            unset($params['marking']);
        }

        $object = $this->applyTransition($object, $workflowName, $transition, $marking);

        return $this->transformToVersionWorkflowModel($object, $workflowName);
    }

    /**
     * @param mixed       $object
     * @param string|null $workflowName
     *
     * @return string
     */
    protected function getMarkingProperty($object, ?string $workflowName)
    {
        $workflow = $this->workflows->get($object, $workflowName);

        return $workflow->getMarkingStore()->getProperty();
    }

    /**
     * @param mixed       $object
     * @param string|null $workflowName
     *
     * @return string
     */
    protected function getWorkflowName($object, ?string $workflowName)
    {
        $workflow = $this->workflows->get($object, $workflowName);

        return $workflow->getName();
    }

    /**
     * @param mixed       $object
     * @param string|null $workflowName
     *
     * @return mixed|null
     */
    protected function getMarkingValue($object, ?string $workflowName)
    {
        $getterMethod = $this->classContains->getGetterMethod(
            $object,
            $this->getMarkingProperty($object, $workflowName)
        );

        if ($getterMethod) {
            return $object->{$getterMethod}();
        }

        return null;
    }
}
