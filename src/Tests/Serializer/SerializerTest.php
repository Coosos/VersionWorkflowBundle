<?php

namespace Coosos\VersionWorkflowBundle\Tests\Serializer;

use Coosos\VersionWorkflowBundle\EventSubscriber\Serializer\MapSubscriber;
use Coosos\VersionWorkflowBundle\Tests\Example\AbstractExample;
use Coosos\VersionWorkflowBundle\Tests\Model\News;
use Coosos\VersionWorkflowBundle\Utils\ClassContains;
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
     */
    public function testSerializeExample1()
    {
        $example = $this->getExample(1);
        $build = $this->builder->build();
        $newsSerialized = $build->serialize($example->generate(), 'json');
        $newsDeserialized = $build->deserialize($newsSerialized, News::class, 'json');

        $this->assertEquals($newsDeserialized, $example->resultDeserialied());
    }

    /**
     * Example 2
     */
    public function testSerializeExample2()
    {
        $example = $this->getExample(2);
        $build = $this->builder->build();
        $newsSerialized = $build->serialize($example->generate(), 'json');
        $newsDeserialized = $build->deserialize($newsSerialized, News::class, 'json');

        $this->assertEquals($newsDeserialized, $example->resultDeserialied());
    }

    /**
     * Example 3
     */
    public function testSerializeExample3()
    {
        $example = $this->getExample(3);
        $build = $this->builder->build();
        $newsSerialized = $build->serialize($example->generate(), 'json');
        $newsDeserialized = $build->deserialize($newsSerialized, News::class, 'json');

        $this->assertEquals($newsDeserialized, $example->resultDeserialied());
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
