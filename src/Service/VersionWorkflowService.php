<?php

namespace Coosos\VersionWorkflowBundle\Service;

use Coosos\VersionWorkflowBundle\Model\VersionWorkflow;
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
     * @param string|null                $transition
     * @return VersionWorkflowTrait|mixed
     */
    public function applyTransition($object, $transition = null)
    {
        $workflow = $this->workflows->get($object);

        if (is_null($transition)) {
            $marking = $workflow->getDefinition()->getInitialPlace();
            $setterMethod = $this->classContains->getSetterMethod($object, $this->getMarkingProperty($object));
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
     * @param VersionWorkflowTrait $object
     * @param array $params
     * @return VersionWorkflow
     */
    public function transformToVersionWorkflowModel($object, $params = [])
    {
        $versionWorkflow = new VersionWorkflow();
        $versionWorkflow->setWorkflowName($this->getWorkflowName($object));
        $versionWorkflow->setModelName(get_class($object));

        if ($object->getVersionWorkflow()) {
            $versionWorkflow->setInherit($object->getVersionWorkflow());
        }

        $versionWorkflow->setObjectSerialized($this->serializerService->serialize($object, 'json', $params));
        $versionWorkflow->setMarking($this->getMarkingValue($object));

        return $versionWorkflow;
    }

    /**
     * @param mixed $object
     * @return string
     */
    protected function getMarkingProperty($object)
    {
        $workflow = $this->workflows->get($object);

        return $workflow->getMarkingStore()->getProperty();
    }

    /**
     * @param mixed $object
     * @return string
     */
    protected function getWorkflowName($object)
    {
        $workflow = $this->workflows->get($object);

        return $workflow->getName();
    }

    /**
     * @param mixed $object
     * @return null
     */
    protected function getMarkingValue($object)
    {
        $getterMethod = $this->classContains->getGetterMethod($object, $this->getMarkingProperty($object));
        if ($getterMethod) {
            return $object->{$getterMethod}();
        }

        return null;
    }
}
