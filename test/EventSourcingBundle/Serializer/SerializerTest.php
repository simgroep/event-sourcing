<?php

namespace Simgroep\EventSourcing\EventSourcingBundle\Serializer;

use Broadway\Bundle\BroadwayBundle\BroadwayBundle;
use Broadway\Domain\DateTime;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Simgroep\EventSourcing\EventSourcingBundle\SimgroepEventSourcingBundle;
use Simgroep\EventSourcing\Messaging\GenericMessage;
use Spray\BundleIntegration\ORMIntegrationTestCase;
use Spray\SerializerBundle\SpraySerializerBundle;
use stdClass;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SerializerTest extends ORMIntegrationTestCase
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
        });
    }

    public function loadFixtures()
    {

    }

    public function serializeTargetProvider()
    {
        return array(
            array(
                new DomainMessage('foo', 1, new Metadata(array()), new stdClass, DateTime::now())
            ),
            array(
                new GenericMessage('foo', new DomainEventStream(array(new stdClass)))
            )
        );
    }

    /**
     * @dataProvider serializeTargetProvider
     */
    public function testSerialize($target)
    {
        $serializer = $this->createContainer()->get('spray_serializer');
//        $serialized = $serializer->serialize($target);
//        $this->assertEquals(
//            $target,
//            $serializer->deserialize(get_class($target), $serialized)
//        );
    }
}
