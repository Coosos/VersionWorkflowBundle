<?php

namespace Coosos\VersionWorkflowBundle\Tests\Service;

use Coosos\VersionWorkflowBundle\Model\VersionWorkflowModel;
use Coosos\VersionWorkflowBundle\Model\VersionWorkflowTrait;
use Coosos\VersionWorkflowBundle\Tests\AbstractTestCase;

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
     * Test apply draft transition
     */
    public function testApplyDraftTransition()
    {
        $marking = 'draft';
        $example = $this->getExample(1);
        $news = $example->generate();
        $news = $this->versionWorkflowService->applyTransition($news);

        $newsResult = $example->resultDeserialied();
        $newsResult->setMarking($marking);

        $this->assertEquals($newsResult->getMarking(), $news->getMarking());

        return [$news, $newsResult];
    }

    /**
     * Test transfrom to version workflow model
     *
     * @param array $data
     *
     * @return array
     * @depends testApplyDraftTransition
     */
    public function testTransformToVersionWorkflowModel(array $data)
    {
        $news = $data[0];
        $newsResult = $data[1];

        /** @var VersionWorkflowModel $versionWorkflowModel */
        $versionWorkflowModel = $this->versionWorkflowService->transformToVersionWorkflowModel($news);
        $dataSerialized = $this->jmsSerializer->serialize($newsResult, 'json');

        $this->assertEquals($newsResult->getMarking(), $versionWorkflowModel->getMarking());
        $this->assertEquals($news, $versionWorkflowModel->getOriginalObject());
        $this->assertEquals($dataSerialized, $versionWorkflowModel->getObjectSerialized());

        $this->assertEquals(self::DEFAULT_WORKFLOW_NAME, $news->getWorkflowName());
        $this->assertEquals(self::DEFAULT_WORKFLOW_NAME, $versionWorkflowModel->getWorkflowName());

        return compact('versionWorkflowModel', 'newsResult');
    }

    /**
     * @param array $data
     * @depends testTransformToVersionWorkflowModel
     */
    public function testTransformVersionWorkflowToOriginalObject(array $data)
    {
        /** @var VersionWorkflowModel $versionWorkflowModel */
        $versionWorkflowModel = $data['versionWorkflowModel'];

        /** @var VersionWorkflowTrait $newsResult */
        $newsResult = $data['newsResult'];
        $newsResult->setVersionWorkflow($versionWorkflowModel);
        $newsResult->setVersionWorkflowFakeEntity(true);

        $object = $this->versionWorkflowService->transformToObject($versionWorkflowModel);

        $this->assertEquals($newsResult, $object);
    }
}
