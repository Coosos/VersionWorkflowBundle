<?php

namespace Coosos\VersionWorkflowBundle\DependencyInjection;

use Coosos\VersionWorkflowBundle\Model\VersionWorkflowConfiguration;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

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
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $this->transformConfigurationArray($config, $container);
    }

    /**
     * Transform array configuration to model and add to service
     *
     * @param array            $config
     * @param ContainerBuilder $container
     *
     * @return CoososVersionWorkflowExtension
     */
    public function transformConfigurationArray(array $config, ContainerBuilder $container)
    {
        $versionWorkflowConfiguration = new Definition(VersionWorkflowConfiguration::class);
        $versionWorkflowConfiguration->addArgument($config);

        $container->setDefinition(VersionWorkflowConfiguration::class, $versionWorkflowConfiguration);

        return $this;
    }
}
