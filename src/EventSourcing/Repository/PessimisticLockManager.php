<?php

namespace Bartdezwaan\EventSourcing\Repository;

use Bartdezwaan\EventSourcing\Repository\Exception\ConcurrencyException;
use Broadway\Domain\AggregateRoot;
use NinjaMutex\Lock\LockInterface;

class PessimisticLockManager implements LockManager
{
    /**
     * @var LockInterface 
     */
    private $lock;
    
    /**
     * @param LockInterface $lock
     */
    public function __construct(LockInterface $lock)
    {
        $this->lock = $lock;
    }
    
    /**
     * {@inheritdoc}
     */
    public function obtain(AggregateRoot $aggregate)
    {
        if ( ! $this->lock->acquireLock($aggregate->getAggregateRootId())) {
            throw new ConcurrencyException(sprintf(
                'Could not obtain lock for %s with id %s',
                get_class($aggregate),
                $aggregate->getAggregateRootId()
            ));
        }
        
    }

    /**
     * {@inheritdoc}
     */
    public function release(AggregateRoot $aggregate)
    {
        $this->lock->releaseLock($aggregate->getAggregateRootId());
    }
}
