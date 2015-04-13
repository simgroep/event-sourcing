<?php

namespace Simgroep\EventSourcing\Repository;

use Simgroep\EventSourcing\TestCase;
use NinjaMutex\Lock\LockInterface;

class PessimisticLockManagerTest extends TestCase
{
    /**
     * @var LockInterface
     */
    private $lock;
    
    protected function setUp()
    {
        $this->lock = $this->getMock('NinjaMutex\Lock\LockInterface');
    }
    
    protected function createManager()
    {
        return new PessimisticLockManager($this->lock);
    }
    
    public function testNotObtained()
    {
        $this->assertFalse($this->createManager()->isObtained('foo'));
    }
    
    public function testObtainLock()
    {
        $this->lock
            ->expects($this->once())
            ->method('acquireLock')
            ->with($this->equalTo('foo'))
            ->will($this->returnValue(true));
        
        $manager = $this->createManager();
        $manager->obtain('foo');
        $this->assertTrue($manager->isObtained('foo'));
    }
    
    public function testReleaseLock()
    {
        $this->lock
            ->expects($this->at(0))
            ->method('acquireLock')
            ->with($this->equalTo('foo'))
            ->will($this->returnValue(true));
        $this->lock
            ->expects($this->at(1))
            ->method('releaseLock')
            ->with($this->equalTo('foo'))
            ->will($this->returnValue(true));
        
        $manager = $this->createManager();
        $manager->obtain('foo');
        $manager->release('foo');
        $this->assertFalse($manager->isObtained('foo'));
    }
    
    public function testOnlyReleaseLockForThisThread()
    {
        $this->lock
            ->expects($this->never())
            ->method('releaseLock');
        
        $this->createManager()->release('foo');
    }
    
    public function testAlreadyLocked()
    {
        $this->lock
            ->expects($this->once())
            ->method('acquireLock')
            ->with($this->equalTo('foo'))
            ->will($this->returnValue(false));
        
        $this->setExpectedException('Simgroep\EventSourcing\Repository\Exception\ConcurrencyException');
        $this->createManager()->obtain('foo');
    }
}
