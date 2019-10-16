<?php

namespace Coosos\VersionWorkflowBundle\Tests\Generate;

use Coosos\VersionWorkflowBundle\Tests\Model\Comment;
use Coosos\VersionWorkflowBundle\Tests\Utils\Random;
use Exception;

/**
 * Class GenerateComment
 *
 * @package Coosos\VersionWorkflowBundle\Tests\Generate
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
class GenerateComment
{
    private static $id = 0;

    /**
     * @param bool       $withId
     * @param mixed|null $news
     * @param mixed|null $user
     *
     * @return Comment
     * @throws Exception
     */
    public function generate($withId = false, $news = null, $user = null)
    {
        $random = new Random();

        $comment = new Comment();
        $comment->setId(($withId) ? self::$id++ : null);
        $comment->setUser($user);
        $comment->setNews($news);
        $comment->setContent($random->randomText(50));

        return $comment;
    }
}
