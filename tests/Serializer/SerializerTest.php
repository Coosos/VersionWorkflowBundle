<?php

namespace Coosos\VersionWorkflowBundle\Tests\Serializer;

use Coosos\VersionWorkflowBundle\Tests\AbstractTestCase;
use Coosos\VersionWorkflowBundle\Tests\Example\AbstractExample;
use Coosos\VersionWorkflowBundle\Tests\Model\News;
use Generator;

/**
 * Class SerializerTest
 *
 * @package Coosos\VersionWorkflowBundle\Tests\Serializer
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
class SerializerTest extends AbstractTestCase
{
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
        $build = $this->jmsSerializer;
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
