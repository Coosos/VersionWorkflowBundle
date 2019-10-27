<?php

namespace Coosos\VersionWorkflowBundle\Tests;

use Coosos\VersionWorkflowBundle\EventSubscriber\Serializer\MapSubscriber;
use Coosos\VersionWorkflowBundle\Service\VersionWorkflowService;
use Coosos\VersionWorkflowBundle\Tests\Example\AbstractExample;
use Coosos\VersionWorkflowBundle\Utils\ClassContains;
use JMS\Serializer\EventDispatcher\EventDispatcher;
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
     * Set up
     */
    protected function setUp(): void
    {
        $builder = SerializerBuilder::create();
        $builder->configureListeners(function (EventDispatcher $dispatcher) {
            $dispatcher->addSubscriber(new MapSubscriber(new ClassContains()));
        });

        $this->jmsSerializer = $builder->build();

        $registry = $this->getRegistryMock();
        $classContains = new ClassContains();
        $this->versionWorkflowService = new VersionWorkflowService(
            $this->jmsSerializer,
            $registry,
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
}
