<?php

namespace Simgroep\EventSourcing\EventSourcingBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AsyncEventListenerCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $listeners = $container->findTaggedServiceIds('spray.domain.async_event_listener');
        $target = $container->getDefinition('spray_event_sourcing.event_handling.async_event_bus');
        
        foreach ($listeners as $id => $attrs) {
            foreach ($attrs as $attr) {
                $target->addMethodCall('subscribe', array(new Reference($id)));
            }
        }
    }
}
