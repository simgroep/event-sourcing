<?php

namespace Simgroep\EventSourcing\Repository;

use Simgroep\EventSourcing\Repository\Exception\ConcurrencyException;
use Simgroep\EventSourcing\TestCase;
use Broadway\Domain\AggregateRoot;
use Broadway\Repository\RepositoryInterface;
use RuntimeException;

class LockingRepositoryTest extends TestCase
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
     * @var AggregateRoot
     */
    private $aggregate;
    
    protected function setUp()
    {
        $this->lockManager = $this->getMock('Simgroep\EventSourcing\Repository\LockManager');
        $this->repository = $this->getMock('Broadway\Repository\RepositoryInterface');
        $this->aggregate = $this->getMock('Broadway\Domain\AggregateRoot');
        
        $this->aggregate
            ->expects($this->any())
            ->method('getAggregateRootId')
            ->will($this->returnValue('foo'));
    }
    
    protected function createRepository()
    {
        return new LockingRepository($this->lockManager, $this->repository);
    }
    
    public function testSaveReleasesRepository()
    {
        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($this->identicalTo($this->aggregate));
        $this->lockManager
            ->expects($this->once())
            ->method('release')
            ->with($this->equalTo('foo'));
        
        $this->createRepository()->save($this->aggregate);
    }
    
    public function testLoadLocksRepository()
    {
        $this->lockManager
            ->expects($this->once())
            ->method('obtain')
            ->with($this->equalTo('foo'));
        $this->repository
            ->expects($this->once())
            ->method('load')
            ->with($this->equalTo('foo'));
        
        $this->createRepository()->load('foo');
    }
    
    public function testReleaseLockOnLockingError()
    {
        $this->lockManager
            ->expects($this->once())
            ->method('obtain')
            ->with($this->equalTo('foo'))
            ->will($this->throwException(new ConcurrencyException('Concurrency')));
        $this->repository
            ->expects($this->never())
            ->method('load');
        $this->lockManager
            ->expects($this->once())
            ->method('release')
            ->with($this->equalTo('foo'));
        
        $this->createRepository()->load('foo');
    }
    
    public function testReleaseLockOnRepositoryError()
    {
        $this->lockManager
            ->expects($this->once())
            ->method('obtain')
            ->with($this->equalTo('foo'));
        $this->repository
            ->expects($this->once())
            ->method('load')
            ->will($this->throwException(new RuntimeException('Runtime')));
        $this->lockManager
            ->expects($this->once())
            ->method('release')
            ->with($this->equalTo('foo'));
        
        $this->createRepository()->load('foo');
    }
}
