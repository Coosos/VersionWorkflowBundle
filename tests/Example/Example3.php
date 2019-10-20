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
        $author = $this->generateUser('User10', 'user@example.com');
        $news = $this->generateNews(
            'Hello world',
            'This day is ...',
            new DateTime('2019-10-14T20:54:59+00:00'),
            $author,
            5
        );

        $comments = new ArrayCollection();
        for ($i = 0; $i < 5; $i++) {
            $comment = $this->generateComment($news, 'content' . $i, new DateTime('2019-10-14T20:23:59+00:00'));
            if ($i === 2) {
                $comment->setUser($this->generateUser('User' . $i, 'user' . $i . '@example.com', $i));
            }

            $comments->add($comment);
        }

        $news->setComments($comments);

        return $news;
    }
}
