<?php

namespace Simgroep\EventSourcing\Repository;

use Broadway\EventHandling\EventBusInterface;
use Broadway\EventSourcing\AggregateFactory\AggregateFactoryInterface;
use Broadway\EventSourcing\EventSourcingRepository;
use Broadway\EventStore\EventStoreInterface;
use Simgroep\EventSourcing\TestCase;

class LockingRepositoryFactoryTest extends TestCase
{
    /**
     * @var LockManager
     */
    private $lockManager;
    
    /**
     * @var EventStoreInterface
     */
    private $eventStore;
    
    /**
     * @var EventBusInterface
     */
    private $eventBus;
    
    /**
     * @var AggregateFactoryInterface
     */
    private $aggregateFactory;
    
    protected function setUp()
    {
        $this->lockManager = $this->getMock('Simgroep\EventSourcing\Repository\LockManager');
        $this->eventStore = $this->getMock('Broadway\EventStore\EventStoreInterface');
        $this->eventBus = $this->getMock('Broadway\EventHandling\EventBusInterface');
        $this->aggregateFactory = $this->getMock('Broadway\EventSourcing\AggregateFactory\AggregateFactoryInterface');
    }
    
    protected function createFactory()
    {
        return new LockingRepositoryFactory(
            $this->lockManager,
            $this->eventStore,
            $this->eventBus,
            $this->aggregateFactory
        );
    }
    
    public function testBuild()
    {
        $expected = new LockingRepository(
            $this->lockManager,
            new EventSourcingRepository(
                $this->eventStore,
                $this->eventBus,
                'Simgroep\EventSourcing\Repository\TestAssets\Aggregate',
                $this->aggregateFactory
            )
        );
        $this->assertEquals(
            $expected,
            $this->createFactory()->build('Simgroep\EventSourcing\Repository\TestAssets\Aggregate')
        );
    }
}
