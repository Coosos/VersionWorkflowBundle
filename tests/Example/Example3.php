<?php

namespace Coosos\VersionWorkflowBundle\Tests\Example;

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
        $news = $this->generateNews(
            'Hello world',
            'This day is ...',
            new DateTime('2019-10-14T20:54:59+00:00'),
            null,
            5
        );

        $comments = new ArrayCollection();
        for ($i = 0; $i < 5; $i++) {
            $comment = $this->generateComment($news, 'content' . $i, new DateTime('2019-10-14T20:23:59+00:00'));
            $comments->add($comment);

//            $user = $generateUser->generate();
//            $comment = $generateComment->generate(false, $news, $user);
//            $comment->setCreatedAt(new DateTime('2019-10-14T20:23:59+00:00'));
//            $comments->add($comment);
//            if ($i === 0) {
//                $news->setAuthor($user);
//            }
        }

        $news->setComments($comments);

        return $news;
    }
}
