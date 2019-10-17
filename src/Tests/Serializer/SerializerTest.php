<?php

namespace Coosos\VersionWorkflowBundle\Tests\Serializer;

use Coosos\VersionWorkflowBundle\EventSubscriber\Serializer\MapSubscriber;
use Coosos\VersionWorkflowBundle\Tests\Example\AbstractExample;
use Coosos\VersionWorkflowBundle\Tests\Model\News;
use Coosos\VersionWorkflowBundle\Utils\ClassContains;
use Generator;
use JMS\Serializer\EventDispatcher\EventDispatcher;
use JMS\Serializer\SerializerBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Class SerializerTest
 *
 * @package Coosos\VersionWorkflowBundle\Tests\Serializer
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
class SerializerTest extends TestCase
{
    /**
     * @var SerializerBuilder
     */
    protected $builder;

    /**
     * Set up
     */
    protected function setUp()
    {
        $this->builder = SerializerBuilder::create();
        $this->builder->configureListeners(function (EventDispatcher $dispatcher) {
            $dispatcher->addSubscriber(new MapSubscriber(new ClassContains()));
        });
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
        $build = $this->builder->build();
        $newsSerialized = $build->serialize($example->generate(), 'json');
        $newsDeserialized = $build->deserialize($newsSerialized, News::class, 'json');

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
