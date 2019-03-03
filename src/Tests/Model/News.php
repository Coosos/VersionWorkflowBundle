<?php

namespace Coosos\VersionWorkflowBundle\Tests\Model;

use Doctrine\Common\Collections\ArrayCollection;
use phpDocumentor\Reflection\Types\Array_;

/**
 * Class News
 *
 * @package Coosos\VersionWorkflowBundle\Tests\Model
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
class News
{
    /**
     * @var int|null
     */
    private $id;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $content;

    /**
     * @var User
     */
    private $author;

    /**
     * @var Comment[]|null
     */
    private $comments;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
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
     * @return News
     */
    public function setId(?int $id): News
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return News
     */
    public function setTitle(string $title): News
    {
        $this->title = $title;

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
     * @return News
     */
    public function setContent(string $content): News
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return User
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @param User $author
     * @return News
     */
    public function setAuthor($author): News
    {
        $this->author = $author;

        return $this;
    }

    /**
     * @return Comment[]|null
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * @param Comment[]|null $comments
     * @return News
     */
    public function setComments($comments): News
    {
        $this->comments = $comments;

        return $this;
    }

    /**
     * @param Comment $comment
     * @return News
     */
    public function addComments(Comment $comment): News
    {
        $this->comments[] = $comment;

        return $this;
    }
}
