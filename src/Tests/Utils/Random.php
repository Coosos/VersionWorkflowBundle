<?php

namespace Coosos\VersionWorkflowBundle\Tests\Utils;

/**
 * Class Random
 *
 * @package Coosos\VersionWorkflowBundle\Tests\Utils
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
class Random
{
    /**
     * @param int $n
     * @return string
     */
    public function randomText($n = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';

        for ($i = 0; $i < $n; $i++) {
            $index = rand(0, strlen($characters) - 1);
            $randomString .= $characters[$index];
        }

        return $randomString;
    }
}
