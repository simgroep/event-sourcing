<?php

namespace Simgroep\EventSourcing\EventSourcingBundle;

use Simgroep\EventSourcing\EventSourcingBundle\DependencyInjection\CompilerPass\TaggedQueueCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SimgroepEventSourcingBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new TaggedQueueCompilerPass());
    }

}
