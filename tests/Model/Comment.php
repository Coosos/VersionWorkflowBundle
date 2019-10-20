<?php

namespace Coosos\VersionWorkflowBundle\Tests\Model;

use DateTime;
use Exception;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class Comment
 *
 * @package Coosos\VersionWorkflowBundle\Tests\Model
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
class Comment
{
    /**
     * @var int|null
     *
     * @Serializer\Type("int")
     */
    private $id;

    /**
     * @var News|null
     *
     * @Serializer\Type("Coosos\VersionWorkflowBundle\Tests\Model\News")
     */
    private $news;

    /**
     * @var User|null
     *
     * @Serializer\Type("Coosos\VersionWorkflowBundle\Tests\Model\User")
     */
    private $user;

    /**
     * @var string
     *
     * @Serializer\Type("string")
     */
    private $content;

    /**
     * @var DateTime
     *
     * @Serializer\Type("DateTime")
     */
    private $createdAt;

    /**
     * Comment constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->createdAt = new DateTime();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     * @return Comment
     */
    public function setId(?int $id): Comment
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return News|null
     */
    public function getNews()
    {
        return $this->news;
    }

    /**
     * @param News|null $news
     * @return Comment
     */
    public function setNews($news): Comment
    {
        $this->news = $news;

        return $this;
    }

    /**
     * @return User|null
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User|null $user
     * @return Comment
     */
    public function setUser($user): Comment
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @param string $content
     * @return Comment
     */
    public function setContent(string $content): Comment
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime $createdAt
     * @return Comment
     */
    public function setCreatedAt($createdAt): Comment
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
