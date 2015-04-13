<?php

namespace Simgroep\EventSourcing\Repository;

use ConcurrencyException;

interface LockManager
{
    /**
     * Obtain a lock.
     * 
     * @param string $aggregateId
     * 
     * @return void
     * 
     * @throws ConcurrencyException If lock could not be obtained for $aggregateId
     */
    public function obtain($aggregateId);
    
    /**
     * Check if a lock was obtained (by the current thread).
     * 
     * @param string $aggregateId
     * 
     * @return boolean
     */
    public function isObtained($aggregateId);
    
    /**
     * If locked, release it.
     * 
     * @param string $aggregateId
     * 
     * @return void
     */
    public function release($aggregateId);
}
