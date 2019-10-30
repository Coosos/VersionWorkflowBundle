<?php

namespace Coosos\VersionWorkflowBundle\Tests\Service;

use Coosos\VersionWorkflowBundle\Model\VersionWorkflowModel;
use Coosos\VersionWorkflowBundle\Model\VersionWorkflowTrait;
use Coosos\VersionWorkflowBundle\Serializer\Exclusion\FieldsListExclusionStrategy;
use Coosos\VersionWorkflowBundle\Service\SerializerService;
use Coosos\VersionWorkflowBundle\Tests\AbstractTestCase;
use Generator;

/**
 * Class VersionWorkflowServiceTest
 * Test version workflow service
 *
 * @package Coosos\VersionWorkflowBundle\Tests\Service
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
class VersionWorkflowServiceTest extends AbstractTestCase
{
    /**
     * Test version workflow service
     *
     * @param int $exampleNumber
     *
     * @dataProvider getExampleProviderList
     */
    public function testVersionWorkflowServiceProcess(int $exampleNumber)
    {
        $example = $this->getExample($exampleNumber);
        $news = $example->generate();
        $newsResult = $example->resultDeserialied();

        $this->nextTestApplyTransition($news, $newsResult);
    }

    /**
     * Test apply transition
     *
     * @param mixed  $news
     * @param mixed  $newsResult
     * @param string $marking
     */
    public function nextTestApplyTransition($news, $newsResult, string $marking = 'draft')
    {
        $news = $this->versionWorkflowService->applyTransition($news, null, null, $marking);
        $newsResult->setMarking($marking);

        $this->assertEquals($newsResult->getMarking(), $news->getMarking());

        $this->nextTestTransformToVersionWorkflowModel(compact('news', 'newsResult'));
    }

    /**
     * Test transfrom to version workflow model
     *
     * @param array $data
     */
    public function nextTestTransformToVersionWorkflowModel(array $data)
    {
        $news = $data['news'];
        $newsResult = $data['newsResult'];

        /** @var VersionWorkflowModel $versionWorkflowModel */
        $versionWorkflowModel = $this->versionWorkflowService->transformToVersionWorkflowModel($news);
        $dataSerialized = $this->jmsSerializer->serialize(
            $newsResult, SerializerService::SERIALIZE_FORMAT,
            $this->getSerializerConext()
        );

        /** @var VersionWorkflowTrait $deserializeData */
        $deserializeData = $this->jmsSerializer->deserialize(
            $versionWorkflowModel->getObjectSerialized(),
            $versionWorkflowModel->getModelName(),
            SerializerService::SERIALIZE_FORMAT
        );

        $this->assertEquals(null, $deserializeData->getVersionWorkflow());
        $this->assertEquals($newsResult->getMarking(), $versionWorkflowModel->getMarking());
        $this->assertEquals($news, $versionWorkflowModel->getOriginalObject());
        $this->assertEquals($dataSerialized, $versionWorkflowModel->getObjectSerialized());
        $this->assertEquals(self::DEFAULT_WORKFLOW_NAME, $news->getWorkflowName());
        $this->assertEquals(self::DEFAULT_WORKFLOW_NAME, $versionWorkflowModel->getWorkflowName());
        foreach (FieldsListExclusionStrategy::IGNORE_FIELDS as $field) {
            $this->assertStringNotContainsString($field, $versionWorkflowModel->getObjectSerialized());
        }

        $this->nextTestTransformVersionWorkflowToOriginalObject(compact('versionWorkflowModel', 'newsResult'));
    }

    /**
     * @param array $data
     */
    public function nextTestTransformVersionWorkflowToOriginalObject(array $data)
    {
        /** @var VersionWorkflowModel $versionWorkflowModel */
        $versionWorkflowModel = $data['versionWorkflowModel'];

        /** @var VersionWorkflowTrait $newsResult */
        $newsResult = $data['newsResult'];
        $newsResult->setVersionWorkflow($versionWorkflowModel);
        $newsResult->setVersionWorkflowFakeEntity(true);

        $object = $this->versionWorkflowService->transformToObject($versionWorkflowModel);

        $this->assertEquals($newsResult, $object);

        switch ($versionWorkflowModel->getMarking()) {
            case 'draft':
                $this->nextTestApplyTransition($object, $newsResult, 'validation');
                break;
            case 'validation':
                $this->nextTestApplyTransition($object, $newsResult, 'publish');
                break;
        }
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
