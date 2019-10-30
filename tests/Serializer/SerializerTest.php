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
            $this->getSerializerConext()
        );

        $newsDeserialized = $build->deserialize(
            $newsSerialized,
            News::class,
            SerializerService::SERIALIZE_FORMAT
        );

        $this->assertEquals($newsDeserialized, $example->resultDeserialied());
    }

    public function testSerializeExampleWithVersionWorkflowModel()
    {
        $example = $this->getExample(1);
        $exampleGenerate = $example->generate();
        $exampleGenerate->setVersionWorkflow(new VersionWorkflowModel());

        $newsSerialized = $this->jmsSerializer->serialize(
            $exampleGenerate,
            SerializerService::SERIALIZE_FORMAT,
            $this->getSerializerConext()
        );

        $newsDeserialized = $this->jmsSerializer->deserialize(
            $newsSerialized,
            News::class,
            SerializerService::SERIALIZE_FORMAT
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
