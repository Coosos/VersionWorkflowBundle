<?php

namespace Coosos\VersionWorkflowBundle\Tests\Model;

use Coosos\VersionWorkflowBundle\Model\VersionWorkflowTrait;

/**
 * Class News
 *
 * @package Coosos\VersionWorkflowBundle\Tests\Model
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
class News
{
    use VersionWorkflowTrait;

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
     * @var User|null
     */
    private $author;

    /**
     * @var Comment[]|null
     */
    private $comments;

    /**
     * @var Tag[]|null
     */
    private $tags;

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
     * @return User|null
     */
    public function getAuthor(): ?User
    {
        return $this->author;
    }

    /**
     * @param User|null $author
     * @return News
     */
    public function setAuthor(?User $author): News
    {
        $this->author = $author;

        return $this;
    }

    /**
     * @return Comment[]|null
     */
    public function getComments(): ?array
    {
        return $this->comments;
    }

    /**
     * @param Comment[]|null $comments
     * @return News
     */
    public function setComments(?array $comments): News
    {
        $this->comments = $comments;

        return $this;
    }

    /**
     * @return Tag[]|null
     */
    public function getTags(): ?array
    {
        return $this->tags;
    }

    /**
     * @param Tag[]|null $tags
     * @return News
     */
    public function setTags(?array $tags): News
    {
        $this->tags = $tags;

        return $this;
    }
}
