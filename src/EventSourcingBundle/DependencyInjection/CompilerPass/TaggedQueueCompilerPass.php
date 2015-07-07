<?php

namespace Simgroep\EventSourcing\EventSourcingBundle\DependencyInjection\CompilerPass;

use Simgroep\EventSourcing\EventHandling\PublishMessageToQueue;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class TaggedQueueCompilerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $tagged = $container->findTaggedServiceIds('simgroep.event.queue');

        foreach ($tagged as $id => $attrs) {
            foreach ($attrs as $attr) {
                if (isset($attr['registry_key'])) {
                    $this->addToRegistry($container, $attr['registry_key'], $id);
                }
            }
            $this->addToEventBus($container, $id);
        }
    }

    /**
     * Add queue to queue registry.
     *
     * @param ContainerBuilder $container
     * @param string           $alias
     * @param string           $id
     *
     * @return void
     */
    protected function addToRegistry(ContainerBuilder $container, $alias, $id)
    {
        $target = $container->getDefinition('simgroep_event_sourcing.messaging.queue_registry');
        $target->addMethodCall('add', array($alias, new Reference($id)));
    }

    /**
     * Build the listener that published messages to the queue.
     *
     * @param ContainerBuilder $container
     * @param string           $id
     *
     * @return void
     */
    protected function addToEventBus(ContainerBuilder $container, $id)
    {
        $target = $container->getDefinition('broadway.event_handling.event_bus');

        $definition = new Definition();
        $definition->setClass(PublishMessageToQueue::class);
        $definition->setArguments(array(
            new Reference($id)
        ));

        $target->addMethodCall('subscribe', array(
            $definition
        ));
    }
}