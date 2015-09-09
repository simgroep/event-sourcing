<?php

namespace Simgroep\EventSourcing\EventSourcingBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ProjectorRegistryCompilerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('sim.projector.registry')) {
            return;
        }
        $definition     = $container->findDefinition('sim.projector.registry');
        $taggedServices = $container->findTaggedServiceIds('projector.replayable');
        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                if (!array_key_exists("repository", $attributes) ){
                    continue;
                }

                $definition->addMethodCall(
                    'addProjector',
                    array(new Reference($id), $id, new Reference($attributes['repository']))
                );
            }
        }
    }

}