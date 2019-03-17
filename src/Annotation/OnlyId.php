<?php

namespace Coosos\VersionWorkflowBundle\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class OnlyId
 *
 * @package Coosos\VersionWorkflowBundle\Annotation
 * @author  Remy Lescallier <lescallier1@gmail.com>
 *
 * @Annotation
 * @Target("PROPERTY")
 */
class OnlyId
{
    /**
     * @var array
     */
    private $identifierAttributes = ['id'];

    /**
     * OnlyId constructor.
     *
     * @param array $options
     */
    public function __construct(array $options)
    {
        $identifierAttributes = null;
        if (!isset($options['identifierAttributes'])) {
            $identifierAttributes = ['id'];
        }

        if (isset($options['identifierAttributes']) && empty($options['identifierAttributes'])) {
            throw new \InvalidArgumentException('identifierAttributes doesn\'t empty !');
        }

        if (!$identifierAttributes) {
            $identifierAttributes = $options['identifierAttributes'];
        }

        $this->identifierAttributes = $identifierAttributes;
    }

    /**
     * @return array
     */
    public function getIdentifierAttributes(): array
    {
        return $this->identifierAttributes;
    }
}
