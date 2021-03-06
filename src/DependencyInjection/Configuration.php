<?php

namespace Coosos\VersionWorkflowBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration
 *
 * @package Coosos\VersionWorkflowBundle\DependencyInjection
 * @author  Remy Lescallier <lescallier1@gmail.com>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('coosos_version_workflow');
        $rootNode
            ->children()
                ->arrayNode('workflows')
                    ->arrayPrototype()
                        ->children()
                            ->arrayNode('auto_merge')
                                ->scalarPrototype()
                            ->end()
                        ->end()
                     ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
