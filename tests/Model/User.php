<?php

namespace Coosos\VersionWorkflowBundle\Tests\Model;

use JMS\Serializer\Annotation as Serializer;

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
     *
     * @Serializer\Type("int")
     */
    private $id;

    /**
     * @var string
     *
     * @Serializer\Type("string")
     */
    private $username;

    /**
     * @var string
     *
     * @Serializer\Type("string")
     */
    private $email;

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

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     * @return User
     */
    public function setEmail(string $email): User
    {
        $this->email = $email;

        return $this;
    }
}
