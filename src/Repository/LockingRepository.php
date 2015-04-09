<?php

namespace Bartdezwaan\EventSourcing\Repository;

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
            parent::load($id);
        } catch (Exception $e) {
            $this->lockManager->release($id);
        }
    }

    /**
     * @param AggregateRoot $aggregate
     * 
     * @return void
     */
    public function save(AggregateRoot $aggregate)
    {
        if ( ! $this->lockManager->isObtained($aggregate->getAggregateRootId())) {
            throw new Exception\RuntimeException(sprintf(
                'Aggregate %s with id %s is not locked',
                get_class($aggregate),
                $aggregate->getAggregateRootId()
            ));
        }
        parent::save($aggregate);
        $this->lockManager->release($aggregate->getAggregateRootId());
    }
}
