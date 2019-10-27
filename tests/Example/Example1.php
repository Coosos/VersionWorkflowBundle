<?php

namespace Coosos\VersionWorkflowBundle\Tests\Example;

use DateTime;
use Exception;

/**
 * Class Example1
 *
 * @package Coosos\VersionWorkflowBundle\Tests\Generate
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
class Example1 extends AbstractExample
{
    /**
     * @var string
     */
    private $newsTitle;

    /**
     * @var string
     */
    private $newsContent;

    /**
     * @var DateTime
     */
    private $newsCreatedAt;

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function generate()
    {
        return $this->generateObject();
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function resultDeserialied()
    {
        return $this->generateObject();
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    protected function generateObject()
    {
        if (!$this->newsTitle) {
            $this->newsTitle = $this->randomText();
        }

        if (!$this->newsContent) {
            $this->newsContent = $this->randomText();
        }

        if (!$this->newsCreatedAt) {
            $this->newsCreatedAt = new DateTime('2019-10-14T20:54:59+00:00');
        }

        return $this->generateNews($this->newsTitle, $this->newsContent, $this->newsCreatedAt);
    }
}
