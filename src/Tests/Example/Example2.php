<?php

namespace Coosos\VersionWorkflowBundle\Tests\Example;

use Coosos\VersionWorkflowBundle\Tests\Generate\GenerateUser;
use Coosos\VersionWorkflowBundle\Tests\Model\News;
use DateTime;
use Exception;

/**
 * Class Example2
 *
 * @package Coosos\VersionWorkflowBundle\Tests\Generate
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
class Example2 extends AbstractExample
{
    /**
     * @return mixed
     * @throws Exception
     */
    public function generate()
    {
        $user = new GenerateUser();

        $news = new News();
        $news->setId(1);

        $news->setTitle('Hello world');
        $news->setContent('This day is ...');
        $news->setCreatedAt(new DateTime('2019-10-14T20:54:59+00:00'));
        $news->setAuthor($user->generate());

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
