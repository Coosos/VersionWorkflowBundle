<?php

namespace Coosos\VersionWorkflowBundle\Tests\Example;

use DateTime;
use Exception;

/**
 * Class Example2
 *
 * @package Coosos\VersionWorkflowBundle\Tests\Generate
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
class Example2 extends AbstractExample
{
    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function generate()
    {
        return $this->generateObject();
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function resultDeserialied()
    {
        return $this->generateObject();
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    protected function generateObject()
    {
        return $this->generateNews(
            'Hello world',
            'This day is ...',
            new DateTime('2019-10-14T20:54:59+00:00'),
            $this->generateUser('User10', 'user@example.com'),
            1
        );
    }
}
