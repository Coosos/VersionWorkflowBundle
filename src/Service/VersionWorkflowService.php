<?php

namespace Coosos\VersionWorkflowBundle\Service;

use Coosos\VersionWorkflowBundle\Entity\VersionWorkflow;
use Coosos\VersionWorkflowBundle\Model\VersionWorkflowModel;
use Coosos\VersionWorkflowBundle\Model\VersionWorkflowTrait;
use Coosos\VersionWorkflowBundle\Utils\ClassContains;
use Coosos\VersionWorkflowBundle\Utils\CloneObject;
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
    private $serializerService;

    /**
     * @var Registry
     */
    private $workflows;

    /**
     * @var ClassContains
     */
    private $classContains;

    /**
     * @var CloneObject
     */
    private $cloneObject;

    /**
     * VersionWorkflowService constructor.
     *
     * @param SerializerService $serializerService
     * @param Registry $workflows
     * @param ClassContains $classContains
     * @param CloneObject $cloneObject
     */
    public function __construct(
        SerializerService $serializerService,
        Registry $workflows,
        ClassContains $classContains,
        CloneObject $cloneObject
    ) {
        $this->serializerService = $serializerService;
        $this->workflows = $workflows;
        $this->classContains = $classContains;
        $this->cloneObject = $cloneObject;
    }

    /**
     * @param VersionWorkflowTrait|mixed $object
     * @param string|null $workflowName
     * @param string|null $transition
     * @return VersionWorkflowTrait|mixed
     */
    public function applyTransition($object, ?string $workflowName, $transition = null)
    {
        $workflow = $this->workflows->get($object, $workflowName);

        if (is_null($transition)) {
            $marking = $workflow->getDefinition()->getInitialPlace();
            $setterMethod = $this->classContains->getSetterMethod($object, $this->getMarkingProperty($object, $workflowName));
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
     * @param array                       $params
     *
     * @return VersionWorkflowModel
     * @throws \ReflectionException
     */
    public function transformToVersionWorkflowModel($object, ?string $workflowName, $params = [])
    {
        $versionWorkflow = new VersionWorkflow();
        $versionWorkflow->setWorkflowName($this->getWorkflowName($object, $workflowName));
        $versionWorkflow->setModelName(get_class($object));
        $versionWorkflow->setMarking($this->getMarkingValue($object, $workflowName));
        $versionWorkflow->setOriginalObject($object);

        if ($object->getVersionWorkflow()) {
            $versionWorkflow->setInherit($object->getVersionWorkflow());
        }

        $objectCloned = $this->cloneObject->cloneObject($object, ['versionWorkflow']);
        $versionWorkflow->setObjectSerialized($this->serializerService->serialize($objectCloned, 'json', $params));

        $object->setVersionWorkflow($versionWorkflow);
        $object->setWorkflowName($workflowName);

        return $versionWorkflow;
    }

    /**
     * Transform VersionWorkflowModel list to deserialized entity
     *
     * @param VersionWorkflowModel[] $versionWorkflows
     * @param array $params
     *
     * @return array
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function transformVersionWorkflowListToObject(array $versionWorkflows, array $params = [])
    {
        $objects = [];

        foreach ($versionWorkflows as $versionWorkflow) {
            $objects[] = $this->transformToObject($versionWorkflow, $params);
        }

        return $objects;
    }

    /**
     * Transform VersionWorkflowModel to deserialized entity
     *
     * @param VersionWorkflowModel|object $object
     * @param array                       $params
     *
     * @return VersionWorkflowTrait|mixed
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function transformToObject($object, array $params = [])
    {
        $entity = $this->serializerService->deserialize(
            $object->getObjectSerialized(),
            $object->getModelName(),
            'json',
            $params
        );

        $entity->setVersionWorkflow($object);

        return $entity;
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
