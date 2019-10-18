<?php

namespace Coosos\VersionWorkflowBundle\Tests\Example;

use Coosos\VersionWorkflowBundle\Model\VersionWorkflowTrait;

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
}
