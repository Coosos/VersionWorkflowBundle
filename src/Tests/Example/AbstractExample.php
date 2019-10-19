<?php

namespace Coosos\VersionWorkflowBundle\Tests\Example;

use Coosos\VersionWorkflowBundle\Model\VersionWorkflowTrait;
use Coosos\VersionWorkflowBundle\Tests\Model\Comment;
use Coosos\VersionWorkflowBundle\Tests\Model\News;
use Coosos\VersionWorkflowBundle\Tests\Model\User;
use DateTime;
use Exception;

/**
 * Class AbstractExample
 *
 * @package Coosos\VersionWorkflowBundle\Tests\Example
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
abstract class AbstractExample
{
    /**
     * @var mixed
     */
    protected $object;

    /**
     * @return VersionWorkflowTrait|mixed
     */
    abstract public function generate();

    /**
     * @return mixed
     */
    abstract public function resultDeserialied();

    /**
     * @return mixed
     */
    abstract protected function generateObject();

    /**
     * Generate news
     *
     * @param string    $title
     * @param string    $content
     * @param DateTime  $createdAt
     * @param User|null $author
     * @param int|null  $id
     *
     * @return News
     * @throws Exception
     */
    protected function generateNews(
        string $title,
        string $content,
        DateTime $createdAt,
        ?User $author = null,
        ?int $id = null
    ): News {
        return (new News())
            ->setId($id)
            ->setTitle($title)
            ->setContent($content)
            ->setCreatedAt($createdAt)
            ->setAuthor($author);
    }

    /**
     * Generate user
     *
     * @param string   $username
     * @param string   $email
     * @param int|null $id
     *
     * @return User
     */
    protected function generateUser(string $username, string $email, ?int $id = null): User
    {
        return (new User())->setUsername($username)->setEmail($email)->setId($id);
    }

    /**
     * Generate comment
     *
     * @param News      $news
     * @param string    $content
     * @param DateTime  $createdAt
     * @param int|null  $id
     * @param User|null $user
     *
     * @return Comment
     * @throws Exception
     */
    protected function generateComment(
        News $news,
        string $content,
        DateTime $createdAt,
        ?int $id = null,
        ?User $user = null
    ): Comment {
        return (new Comment())
            ->setId($id)
            ->setContent($content)
            ->setCreatedAt($createdAt)
            ->setUser($user)
            ->setNews($news);
    }

    /**
     * Generate text
     *
     * @param int $n
     * @return string
     */
    protected function randomText(int $n = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';

        for ($i = 0; $i < $n; $i++) {
            $index = rand(0, strlen($characters) - 1);
            $randomString .= $characters[$index];
        }

        return $randomString;
    }
}
