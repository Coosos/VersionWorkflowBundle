<?php

namespace Coosos\VersionWorkflowBundle\Tests\Generate;

use Coosos\VersionWorkflowBundle\Tests\Model\User;
use Coosos\VersionWorkflowBundle\Tests\Utils\Random;

/**
 * Class GenerateUser
 *
 * @package Coosos\VersionWorkflowBundle\Tests\Generate
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
class GenerateUser
{
    private static $id = 0;

    /**
     * @return User
     */
    public function generate()
    {
        $random = new Random();

        $user = new User();
        $user->setId(self::$id++);
        $user->setUsername($random->randomText(10));
        $user->setEmail($random->randomText(10) . '@' . $random->randomText(5) . '.com');

        return $user;
    }
}
