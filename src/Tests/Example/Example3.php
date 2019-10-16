<?php

namespace Coosos\VersionWorkflowBundle\Tests\Example;

use Coosos\VersionWorkflowBundle\Tests\Generate\GenerateComment;
use Coosos\VersionWorkflowBundle\Tests\Generate\GenerateUser;
use Coosos\VersionWorkflowBundle\Tests\Model\News;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;

/**
 * Class Example3
 *
 * @package Coosos\VersionWorkflowBundle\Tests\Example
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
class Example3 extends AbstractExample
{
    /**
     * @return mixed
     * @throws Exception
     */
    public function generate()
    {
        $generateComment = new GenerateComment();
        $generateUser = new GenerateUser();

        $news = new News();
        $news->setId(5);

        $news->setTitle('Hello world');
        $news->setContent('This day is ...');
        $news->setCreatedAt(new DateTime('2019-10-14T20:54:59+00:00'));

        $comments = new ArrayCollection();
        for ($i = 0; $i < 5; $i++) {
            $user = $generateUser->generate();
            $comment = $generateComment->generate(false, $news, $user);
            $comment->setCreatedAt(new DateTime('2019-10-14T20:23:59+00:00'));
            $comments->add($comment);
            if ($i === 0) {
                $news->setAuthor($user);
            }
        }

        $news->setComments($comments);

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
