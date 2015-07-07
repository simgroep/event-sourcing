<?php

namespace Simgroep\EventSourcing\EventSourcingBundle;

use Broadway\Bundle\BroadwayBundle\BroadwayBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Simgroep\EventSourcing\Messaging\AMQPQueueFactory;
use Simgroep\EventSourcing\Messaging\Queue;
use Simgroep\EventSourcing\Messaging\VoidQueue;
use Spray\BundleIntegration\ORMIntegrationTestCase;
use Spray\SerializerBundle\SpraySerializerBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;

class SimgroepEventSouringBundleTest extends ORMIntegrationTestCase
{
    public function registerBundles()
    {
        return array(
            new FrameworkBundle(),
            new DoctrineBundle(),
            new BroadwayBundle(),
            new SpraySerializerBundle(),
            new SimgroepEventSourcingBundle()
        );
    }
    
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        parent::registerContainerConfiguration($loader);
        $loader->load(function(ContainerBuilder $container) {
            $container->setParameter('database_user', 'test');
            $container->setParameter('database_password', 'test');
            $container->setParameter('database_host', 'test');
            $container->setParameter('broadway.saga.mongodb.storage_suffix', 'test');
            
            
            $lockingRepositoryDefinition = new DefinitionDecorator('simgroep_event_sourcing.locking_repository');
            $lockingRepositoryDefinition->setClass('Simgroep\EventSourcing\Repository\TestAssets\Aggregate');
            $lockingRepositoryDefinition->addArgument('Simgroep\EventSourcing\Repository\TestAssets\Aggregate');
            $container->setDefinition('locking_repository', $lockingRepositoryDefinition);

            $queueDefinition = new Definition();
            $queueDefinition->setClass(VoidQueue::class);
            $queueDefinition->addTag('simgroep.event.queue', array('alias' => 'foo'));
            $container->setDefinition('queue', $queueDefinition);
        });
    }
    
    public function loadFixtures()
    {
        
    }
    
    public function testCommandGateway()
    {
        $this->assertInstanceOf(
            'Simgroep\EventSourcing\CommandHandling\CommandGateway',
            $this->createContainer()->get('command_gateway')
        );
    }
    
    public function testLockingRepository()
    {
        $this->assertInstanceOf(
            'Simgroep\EventSourcing\Repository\LockingRepository',
            $this->createContainer()->get('locking_repository')
        );
    }

    public function testQueueFactory()
    {
        $this->assertInstanceOf(AMQPQueueFactory::class, $this->createContainer()->get('queue_factory'));
    }

    public function testQueueRegistry()
    {
        $registry = $this->createContainer()->get('queue_registry');
        $this->assertInstanceOf(Queue::class, $registry->get('foo'));
    }
    
    /**
     * @return ContainerBuilder
     */
    protected function createContainer()
    {
        return parent::createContainer();
    }
}
