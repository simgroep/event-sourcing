<?php

namespace Simgroep\EventSourcing\EventSourcingBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TaggedQueueCompilerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $target = $container->getDefinition('simgroep_event_sourcing.messaging.queue_registry');
        $tagged = $container->findTaggedServiceIds('simgroep.event.queue');

        foreach ($tagged as $id => $attrs) {
            foreach ($attrs as $attr) {
                $target->addMethodCall('add', array($attr['alias'], new Reference($id)));
            }
        }
    }
}