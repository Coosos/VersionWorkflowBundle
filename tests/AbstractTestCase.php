<?php

namespace Coosos\VersionWorkflowBundle\Tests;

use Coosos\BidirectionalRelation\EventSubscriber\MapDeserializerSubscriber;
use Coosos\BidirectionalRelation\EventSubscriber\MapSerializerSubscriber;
use Coosos\VersionWorkflowBundle\Model\VersionWorkflowConfiguration;
use Coosos\VersionWorkflowBundle\Serializer\Exclusion\FieldsListExclusionStrategy;
use Coosos\VersionWorkflowBundle\Service\SerializerService;
use Coosos\VersionWorkflowBundle\Service\VersionWorkflowService;
use Coosos\VersionWorkflowBundle\Tests\Example\AbstractExample;
use Coosos\VersionWorkflowBundle\Utils\ClassContains;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\EventDispatcher\EventDispatcher;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\SerializerInterface as JmsSerializerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Workflow\DefinitionBuilder;
use Symfony\Component\Workflow\MarkingStore\SingleStateMarkingStore;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * Class AbstractTestCase
 *
 * @package Coosos\VersionWorkflowBundle\Tests
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
abstract class AbstractTestCase extends TestCase
{
    const DEFAULT_WORKFLOW_NAME = 'test_workflow';

    /**
     * @var JmsSerializerInterface
     */
    protected $jmsSerializer;

    /**
     * @var VersionWorkflowService
     */
    protected $versionWorkflowService;

    /**
     * @var SerializationContext
     */
    protected $serializerContext;

    /**
     * @var Registry
     */
    protected $registryWorkflow;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $classContains = new ClassContains();
        $builder = SerializerBuilder::create();
        $builder->configureListeners(function (EventDispatcher $dispatcher) {
            $dispatcher->addSubscriber(new MapSerializerSubscriber());
            $dispatcher->addSubscriber(new MapDeserializerSubscriber());
        });

        $this->jmsSerializer = $builder->build();
        if (!$this->serializerContext) {
            $this->serializerContext = (SerializationContext::create())
                ->addExclusionStrategy(new FieldsListExclusionStrategy($classContains));
        }

        $this->registryWorkflow = $this->getRegistryMock();
        $this->versionWorkflowService = new VersionWorkflowService(
            new SerializerService($this->jmsSerializer, $classContains),
            $this->registryWorkflow,
            $classContains
        );
    }

    /**
     * @param int $number
     *
     * @return AbstractExample
     */
    protected function getExample(int $number)
    {
        $classString = '\Coosos\VersionWorkflowBundle\Tests\Example\Example' . $number;

        return new $classString();
    }

    /**
     * @return MockObject|Registry
     */
    protected function getRegistryMock()
    {
        $definition = new DefinitionBuilder(['draft', 'validation', 'publish']);
        $definition->setInitialPlace('draft');
        $definitionBuild = $definition->build();

        $markingStore = $this->createMock(SingleStateMarkingStore::class);
        $markingStore->method('getProperty')->willReturn('marking');

        $workflowMock = $this->createMock(WorkflowInterface::class);
        $workflowMock->method('getDefinition')->willReturnReference($definitionBuild);
        $workflowMock->method('getMarkingStore')->willReturnReference($markingStore);
        $workflowMock->method('getName')->willReturn(self::DEFAULT_WORKFLOW_NAME);

        $registryMock = $this->createMock(Registry::class);
        $registryMock->method('get')->willReturnReference($workflowMock);

        return $registryMock;
    }

    /**
     * @return SerializationContext
     */
    protected function getSerializerContext()
    {
        return (SerializationContext::create())
            ->addExclusionStrategy(new FieldsListExclusionStrategy(new ClassContains()));
    }

    /**
     * @return DeserializationContext
     */
    protected function getDeserializerContext()
    {
        return (DeserializationContext::create())
            ->addExclusionStrategy(new FieldsListExclusionStrategy(new ClassContains()));
    }

    /**
     * Get version workflow configuration model
     *
     * @return VersionWorkflowConfiguration
     */
    protected function getVersionWorkflowConfiguration()
    {
        $config = [
            'workflows' => [
                self::DEFAULT_WORKFLOW_NAME => [
                    'auto_merge' => ['publish'],
                ],
            ],
        ];

        return new VersionWorkflowConfiguration($config);
    }
}
