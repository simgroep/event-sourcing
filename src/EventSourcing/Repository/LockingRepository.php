<?php

namespace Bartdezwaan\EventSourcing\Repository;

use Broadway\Domain\AggregateRoot;
use Broadway\Repository\RepositoryInterface;

class LockingRepository implements RepositoryInterface
{
    /**
     * @var LockManager
     */
    private $lockManager;
    
    /**
     * @var RepositoryInterface
     */
    private $repository;
    
    /**
     * Requires a $lockManager and a child $repository to which calls are proxied
     * if lock could be obtained.
     * 
     * @param LockManager $lockManager
     * @param RepositoryInterface $repository
     */
    public function __construct(LockManager $lockManager, RepositoryInterface $repository)
    {
        $this->lockManager = $lockManager;
        $this->repository = $repository;
    }
    
    /**
     * @param string $id
     * 
     * @return AggregateRoot
     */
    public function load($id)
    {
        
    }

    /**
     * @param AggregateRoot $aggregate
     * 
     * @return void
     */
    public function save(AggregateRoot $aggregate)
    {
        
    }
}
