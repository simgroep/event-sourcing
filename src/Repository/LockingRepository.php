<?php

namespace Simgroep\EventSourcing\Repository;

use Broadway\Domain\AggregateRoot;
use Broadway\Repository\RepositoryInterface;
use Exception;

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
        try {
            $this->lockManager->obtain($id);
            return $this->repository->load($id);
        } catch (Exception $e) {
            $this->lockManager->release($id);
        }
    }

    /**
     * Releases lock after saving if there was one.
     * 
     * @param AggregateRoot $aggregate
     * 
     * @return void
     */
    public function save(AggregateRoot $aggregate)
    {
        $this->repository->save($aggregate);
        $this->lockManager->release($aggregate->getAggregateRootId());
    }
}
