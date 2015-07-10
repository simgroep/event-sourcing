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
            $definition->addMethodCall(
                'addProjector',
                array(new Reference($id), $this->generateProjectorKey($id))
            );
        }
    }

    /**
     * @param $serviceId
     * @return string
     */
    private function generateProjectorKey($serviceId)
    {
        return str_replace('sim.read_model.projector.','', $serviceId);
    }

}