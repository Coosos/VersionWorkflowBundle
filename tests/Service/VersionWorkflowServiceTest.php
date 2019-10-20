<?php

namespace Coosos\VersionWorkflowBundle\Tests\Service;

use Coosos\VersionWorkflowBundle\Model\VersionWorkflowModel;
use Coosos\VersionWorkflowBundle\Tests\AbstractTestCase;

/**
 * Class VersionWorkflowServiceTest
 *
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
        $news = $this->getExample(1)->generate();
        $news = $this->versionWorkflowService->applyTransition($news);

        $this->assertEquals($marking, $news->getMarking());
    }

    /**
     * Test transfrom to version workflow model
     */
    public function testTransformToVersionWorkflowModel()
    {
        $example = $this->getExample(1);
        $news = $example->generate();
        $news->setMarking('draft');

        $newsResult = $example->resultDeserialied();
        $newsResult->setMarking('draft');

        /** @var VersionWorkflowModel $versionWorkflowModel */
        $versionWorkflowModel = $this->versionWorkflowService->transformToVersionWorkflowModel($news);

        $this->assertEquals('draft', $versionWorkflowModel->getMarking());
        $this->assertEquals($news, $versionWorkflowModel->getOriginalObject());
        $this->assertEquals(
            $this->jmsSerializer->serialize($newsResult, 'json'),
            $versionWorkflowModel->getObjectSerialized()
        );
    }
}
