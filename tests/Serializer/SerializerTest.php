<?php

namespace Coosos\VersionWorkflowBundle\Tests\Serializer;

use Coosos\VersionWorkflowBundle\Model\VersionWorkflowModel;
use Coosos\VersionWorkflowBundle\Service\SerializerService;
use Coosos\VersionWorkflowBundle\Tests\AbstractTestCase;
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
        $newsSerialized = $build->serialize(
            $example->generate(),
            SerializerService::SERIALIZE_FORMAT,
            $this->getSerializerContext()
        );

        $newsDeserialized = $build->deserialize(
            $newsSerialized,
            News::class,
            SerializerService::SERIALIZE_FORMAT,
            $this->getDeserializerContext()
        );

        $this->assertEquals($newsDeserialized, $example->resultDeserialied());
    }

    /**
     * Example
     *
     * @dataProvider getExampleProviderList
     *
     * @param int $nb
     */
    public function testSerializeExampleWithVersionWorkflowModel($nb)
    {
        $example = $this->getExample($nb);
        $exampleGenerate = $example->generate();
        $exampleGenerate->setVersionWorkflow(new VersionWorkflowModel());

        $newsSerialized = $this->jmsSerializer->serialize(
            $exampleGenerate,
            SerializerService::SERIALIZE_FORMAT,
            $this->getSerializerContext()
        );

        $newsDeserialized = $this->jmsSerializer->deserialize(
            $newsSerialized,
            News::class,
            SerializerService::SERIALIZE_FORMAT,
            $this->getDeserializerContext()
        );

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
}
