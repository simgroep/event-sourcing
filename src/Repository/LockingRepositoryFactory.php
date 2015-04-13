<?php

namespace Simgroep\EventSourcing\Repository;

use Broadway\EventHandling\EventBusInterface;
use Broadway\EventSourcing\AggregateFactory\AggregateFactoryInterface;
use Broadway\EventSourcing\EventSourcingRepository;
use Broadway\EventStore\EventStoreInterface;

class LockingRepositoryFactory
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
    
    /**
     * @param LockManager               $lockManager
     * @param EventStoreInterface       $eventStore
     * @param EventBusInterface         $eventBus
     * @param AggregateFactoryInterface $aggregateFactory
     */
    public function __construct(
        LockManager $lockManager,
        EventStoreInterface $eventStore,
        EventBusInterface $eventBus,
        AggregateFactoryInterface $aggregateFactory)
    {
        $this->lockManager = $lockManager;
        $this->eventStore = $eventStore;
        $this->eventBus = $eventBus;
        $this->aggregateFactory = $aggregateFactory;
    }
    
    /**
     * Build a LockingRepository.
     * 
     * @param string $aggregateClass
     * @param array  $eventStreamDecorators
     * 
     * @return LockingRepository
     */
    public function build($aggregateClass, array $eventStreamDecorators = array())
    {
        return new LockingRepository(
            $this->lockManager,
            new EventSourcingRepository(
                $this->eventStore,
                $this->eventBus,
                $aggregateClass,
                $this->aggregateFactory,
                $eventStreamDecorators
            )
        );
    }
}
