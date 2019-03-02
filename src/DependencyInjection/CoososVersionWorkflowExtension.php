<?php

namespace Coosos\VersionWorkflowBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

/**
 * Class CoososVersionWorkflowExtension
 *
 * @package Coosos\VersionWorkflowBundle\DependencyInjection
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
class CoososVersionWorkflowExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new Configuration(), $configs);
    }
}
