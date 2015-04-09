<?php

namespace Bartdezwaan\EventSourcing\Repository;

use Bartdezwaan\EventSourcing\Repository\Exception\ConcurrencyException;
use NinjaMutex\Lock\LockInterface;

class PessimisticLockManager implements LockManager
{
    /**
     * @var LockInterface 
     */
    private $lock;
    
    /**
     * @var array<string>
     */
    private $lockedAggregateIds = array();
    
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
    public function obtain($aggregateId)
    {
        if ( ! $this->lock->acquireLock($aggregateId)) {
            throw new ConcurrencyException(sprintf(
                'Could not obtain lock for aggregate with id %s',
                $aggregateId
            ));
        }
        $this->lockedAggregateIds[$aggregateId] = true;
    }
    
    /**
     * {@inheritdoc}
     */
    public function isObtained($aggregateId)
    {
        return isset($this->lockedAggregateIds[$aggregateId]);
    }
    
    /**
     * {@inheritdoc}
     */
    public function release($aggregateId)
    {
        $this->lock->releaseLock($aggregateId);
        unset($this->lockedAggregateIds[$aggregateId]);
    }
}
