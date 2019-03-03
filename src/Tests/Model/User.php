<?php

namespace Coosos\VersionWorkflowBundle\Tests\Model;

/**
 * Class User
 *
 * @package Coosos\VersionWorkflowBundle\Tests\Model
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
class User
{
    /**
     * @var int|null
     */
    private $id;

    /**
     * @var string
     */
    private $username;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     * @return User
     */
    public function setId(?int $id): User
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param string $username
     * @return User
     */
    public function setUsername(string $username): User
    {
        $this->username = $username;

        return $this;
    }
}
