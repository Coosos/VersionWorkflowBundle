<?php

namespace Coosos\VersionWorkflowBundle\Tests\Service;

use Coosos\VersionWorkflowBundle\EventSubscriber\Serializer\MapSubscriber;
use Coosos\VersionWorkflowBundle\Service\SerializerService;
use Coosos\VersionWorkflowBundle\Tests\Example\AbstractExample;
use Coosos\VersionWorkflowBundle\Tests\Model\News;
use Coosos\VersionWorkflowBundle\Utils\ClassContains;
use Generator;
use JMS\Serializer\EventDispatcher\EventDispatcher;
use JMS\Serializer\SerializerBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Class SerializerServiceTest
 *
 * @package Coosos\VersionWorkflowBundle\Tests\Service
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
class SerializerServiceTest extends TestCase
{
    /**
     * @var SerializerService
     */
    protected $service;

    /**
     * Set up
     */
    protected function setUp()
    {
        $builder = SerializerBuilder::create();
        $builder->configureListeners(function (EventDispatcher $dispatcher) {
            $dispatcher->addSubscriber(new MapSubscriber(new ClassContains()));
        });

        $this->service = new SerializerService($builder->build());
    }

    /**
     * Example 1
     *
     * @dataProvider getExampleProviderList
     *
     * @param int $nb
     */
    public function testSerializeExample($nb)
    {
        $example = $this->getExample($nb);
        $newsSerialized = $this->service->serialize($example->generate(), 'json');
        $newsDeserialized = $this->service->deserialize($newsSerialized, News::class, 'json');

        $this->assertEquals($newsDeserialized, $example->resultDeserialied());
    }

    /**
     * @return Generator
     */
    public function getExampleProviderList()
    {
        yield [1];
        yield [2];
        yield [3];
        yield [4];
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
}
