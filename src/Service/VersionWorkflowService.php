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
     * VersionWorkflowService constructor.
     *
     * @param SerializerService $serializerService
     * @param Registry $workflows
     * @param ClassContains $classContains
     */
    public function __construct(
        SerializerService $serializerService,
        Registry $workflows,
        ClassContains $classContains
    ) {
        $this->serializerService = $serializerService;
        $this->workflows = $workflows;
        $this->classContains = $classContains;
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
     * @param array                       $params
     *
     * @return VersionWorkflowModel
     */
    public function transformToVersionWorkflowModel($object, ?string $workflowName = null, $params = [])
    {
        $versionWorkflow = new VersionWorkflow();
        $versionWorkflow->setWorkflowName($this->getWorkflowName($object, $workflowName));
        $versionWorkflow->setModelName(get_class($object));
        $versionWorkflow->setMarking($this->getMarkingValue($object, $workflowName));
        $versionWorkflow->setOriginalObject($object);

        if ($object->getVersionWorkflow()) {
            $versionWorkflow->setInherit($object->getVersionWorkflow());
        }

        $versionWorkflow->setObjectSerialized($this->cloneAndSerializeObject($object, $params));

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
     */
    public function transformToObject($object, array $params = [])
    {
        $entity = $this->serializerService->deserialize(
            $object->getObjectSerialized(),
            $object->getModelName(),
            'json',
            $params
        );

        $entity->setVersionWorkflowFakeEntity(true);
        $entity->setVersionWorkflow($object);

        return $entity;
    }

    /**
     * Clone and serialize object
     *
     * @param mixed $object
     * @param array $params
     *
     * @return string
     */
    public function cloneAndSerializeObject($object, $params = [])
    {
        return $this->serializerService->serialize($object, 'json', $params);
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

        return $this->transformToVersionWorkflowModel($object, $workflowName, $params);
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
