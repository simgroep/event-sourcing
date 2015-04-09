<?php

namespace Bartdezwaan\EventSourcing\Repository;

use Broadway\Domain\AggregateRoot;
use RuntimeException;

interface LockManager
{
    /**
     * Obtain a lock
     * 
     * @param AggregateRoot $aggregate
     * 
     * @return void
     * 
     * @throws ConcurrencyException If lock could not be obtained for $aggregate
     */
    public function obtain(AggregateRoot $aggregate);
    
    /**
     * If locked, release it.
     * 
     * @param AggregateRoot $aggregate
     * 
     * @return void
     */
    public function release(AggregateRoot $aggregate);
}
