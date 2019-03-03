<?php

namespace Coosos\VersionWorkflowBundle\Tests\Example;

use Coosos\VersionWorkflowBundle\Tests\Model\Comment;
use Coosos\VersionWorkflowBundle\Tests\Model\News;
use Coosos\VersionWorkflowBundle\Tests\Model\User;

class GenerateNews
{
    public function generate()
    {
        return $this->generateNews();
    }

    protected function generateNews()
    {
        $news = new News();
        $news->setTitle($this->randomString());
        $news->setContent($this->randomString());

        $news->setAuthor($this->generateUser());
        for ($i = 0; $i < 10; $i++) {
            $news->addComments($this->generateComment());
        }

        return $news;
    }

    protected function generateUser()
    {
        $user = new User();

        $user->setUsername($this->randomString());

        return $user;
    }

    protected function generateComment()
    {
        $comment = new Comment();
        $comment->setContent($this->randomString());
        $comment->setUser($this->generateUser());

        return $comment;
    }

    protected function randomString()
    {
        return substr(str_shuffle(MD5(microtime())), 0, 10);
    }
}
