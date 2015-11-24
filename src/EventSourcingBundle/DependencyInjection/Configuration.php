<?php

namespace Simgroep\EventSourcing\EventSourcingBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('simgroep_event_sourcing');

        $rootNode
            ->children()
                ->arrayNode('command_path')
                    ->children()
                        ->scalarNode('path')->end()
                        ->scalarNode('namespace')->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
