<?php

namespace Coosos\VersionWorkflowBundle\Tests\Example;

use Coosos\VersionWorkflowBundle\Tests\Model\News;
use Exception;

/**
 * Class Example1
 *
 * @package Coosos\VersionWorkflowBundle\Tests\Generate
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
class Example1 extends AbstractExample
{
    protected $object;

    /**
     * @return News
     * @throws Exception
     */
    public function generate()
    {
        $news = new News();
        $news->setTitle('Hello world');
        $news->setContent('This day is ...');
        $news->setCreatedAt(new \DateTime('2019-10-14T20:54:59+00:00'));

        return $this->object = $news;
    }

    /**
     * @return mixed
     */
    public function resultDeserialied()
    {
        return $this->object;
    }
}
