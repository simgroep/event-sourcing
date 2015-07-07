<?php

namespace Simgroep\EventSourcing\Messaging;

use RuntimeException;
use Simgroep\EventSourcing\TestCase;

class ArrayQueueRegistryTest extends TestCase
{
    private $queue;

    protected function setUp()
    {
        $this->queue = $this->getMock(Queue::class);
    }

    protected function createRegistry()
    {
        return new ArrayQueueRegistry();
    }

    public function testCannotGetNonExistentQueue()
    {
        $this->setExpectedException(RuntimeException::class);
        $this->createRegistry()->get('foo');
    }

    public function testGetAddedQueue()
    {
        $registry = $this->createRegistry();
        $registry->add('foo', $this->queue);
        $this->assertSame($this->queue, $registry->get('foo'));
    }
}