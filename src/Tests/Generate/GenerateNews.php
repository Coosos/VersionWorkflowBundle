<?php

namespace Coosos\VersionWorkflowBundle\Tests\Generate;

use Coosos\VersionWorkflowBundle\Tests\Model\News;
use Coosos\VersionWorkflowBundle\Tests\Utils\Random;

/**
 * Class GenerateNews
 *
 * @package Coosos\VersionWorkflowBundle\Tests\Generate
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
class GenerateNews
{
    private static $id = 0;

    /**
     * @param bool $withId
     * @return News
     * @throws \Exception
     */
    public function generateExample1($withId = false)
    {
        $news = new News();
        $news->setId(($withId) ? self::$id++ : null);

        $news->setTitle($this->getRamdomText(50));
        $news->setContent($this->getRamdomText(200));

        return $news;
    }

    /**
     * @param bool $withId
     * @return News
     * @throws \Exception
     */
    public function generateExample2($withId = false)
    {
        $user = new GenerateUser();

        $news = new News();
        $news->setId(($withId) ? self::$id++ : null);

        $news->setTitle($this->getRamdomText(50));
        $news->setContent($this->getRamdomText(200));
        $news->setAuthor($user->generate());

        return $news;
    }

    /**
     * @param bool $withId
     * @return News
     * @throws \Exception
     */
    public function generateExample3($withId = false)
    {
        $generateComment = new GenerateComment();
        $generateUser = new GenerateUser();

        $news = new News();
        $news->setId(($withId) ? self::$id++ : null);

        $news->setTitle($this->getRamdomText(50));
        $news->setContent($this->getRamdomText(200));
        $news->setAuthor($generateUser->generate());

        $comments = [];
        for ($i = 0; $i < 5; $i++) {
            $comments[] = $generateComment->generate(false, $news, $generateUser->generate());
        }

        $news->setComments($comments);

        return $news;
    }

    /**
     * @param bool $withId
     * @return News
     * @throws \Exception
     */
    public function generateExample4($withId = false)
    {
        $generateComment = new GenerateComment();
        $generateUser = new GenerateUser();

        $news = new News();
        $news->setId(($withId) ? self::$id++ : null);

        $news->setTitle($this->getRamdomText(50));
        $news->setContent($this->getRamdomText(200));
        $news->setAuthor($author = $generateUser->generate());

        $comments = [];
        for ($i = 0; $i < 5; $i++) {
            $user = $generateUser->generate();
            if ($i % 2) {
                $user = $author;
            }

            $comments[] = $generateComment->generate(false, $news, $user);
        }

        $news->setComments($comments);

        return $news;
    }

    /**
     * @param $n
     * @return string
     */
    protected function getRamdomText($n)
    {
        return (new Random())->randomText($n);
    }
}
