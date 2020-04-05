<?php

namespace Coosos\VersionWorkflowBundle\Tests\Utils;

use Coosos\VersionWorkflowBundle\Tests\AbstractTestCase;
use Coosos\VersionWorkflowBundle\Utils\AutoMergeCheck;
use Coosos\VersionWorkflowBundle\Utils\ClassContains;

/**
 * Class AutoMergeCheckTest
 *
 * @package Coosos\VersionWorkflowBundle\Tests\Utils
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
class AutoMergeCheckTest extends AbstractTestCase
{
    /**
     * @var AutoMergeCheck
     */
    protected $autoMergeChecker;

    /**
     * Test is auto merge entity from AutoMergeCheck class
     */
    public function testIsAutoMergeEntity()
    {
        $example = $this->getExample(1);
        $news = $example->generate();
        $news->setWorkflowName(self::DEFAULT_WORKFLOW_NAME);

        $news->setMarking('draft');
        $this->assertFalse($this->autoMergeChecker->isAutoMergeEntity($news));

        $news->setMarking('publish');
        $this->assertTrue($this->autoMergeChecker->isAutoMergeEntity($news));
    }

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->autoMergeChecker = new AutoMergeCheck(
            $this->registryWorkflow,
            new ClassContains(),
            $this->getVersionWorkflowConfiguration()
        );
    }
}
