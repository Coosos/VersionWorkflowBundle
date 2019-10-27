<?php

namespace Coosos\VersionWorkflowBundle\Tests\Model;

use Coosos\VersionWorkflowBundle\Model\VersionWorkflowTrait;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use JMS\Serializer\Annotation as Serializer;

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
     *
     * @Serializer\Type("int")
     */
    private $id;

    /**
     * @var string
     *
     * @Serializer\Type("string")
     */
    private $title;

    /**
     * @var string
     *
     * @Serializer\Type("string")
     */
    private $content;

    /**
     * @var User|null
     *
     * @Serializer\Type("Coosos\VersionWorkflowBundle\Tests\Model\User")
     */
    private $author;

    /**
     * @var Comment[]|null
     *
     * @Serializer\Type("ArrayCollection<Coosos\VersionWorkflowBundle\Tests\Model\Comment>")
     */
    private $comments;

    /**
     * @var Tag[]|null
     */
    private $tags;

    /**
     * @var DateTime
     *
     * @Serializer\Type("DateTime")
     */
    private $createdAt;

    /**
     * @var string
     *
     * @Serializer\Type("string")
     */
    private $marking;

    /**
     * News constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->createdAt = new DateTime();
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
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * @param ArrayCollection|null $comments
     * @return News
     */
    public function setComments($comments): News
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

    /**
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime $createdAt
     * @return News
     */
    public function setCreatedAt($createdAt): News
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return string
     */
    public function getMarking()
    {
        return $this->marking;
    }

    /**
     * @param string $marking
     *
     * @return News
     */
    public function setMarking($marking): News
    {
        $this->marking = $marking;

        return $this;
    }
}
