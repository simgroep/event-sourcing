<?php

namespace Simgroep\EventSourcing\EventHandling;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Simgroep\EventSourcing\Messaging\DomainEventStreamMessage;
use Simgroep\EventSourcing\Messaging\Queue;
use Simgroep\EventSourcing\TestCase;
use stdClass;

class PublishMessageToQueueTest extends TestCase
{
    private $queue;

    protected function setUp()
    {
        $this->queue = $this->getMock(Queue::class);
    }

    protected function createListener()
    {
        return new PublishMessageToQueue($this->queue);
    }

    public function testPublishDomainMessage()
    {
        $payload = new stdClass;
        $domainMessage = new DomainMessage('id', 1, new Metadata(array()), $payload, DateTime::now());

        $this->queue->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(DomainEventStreamMessage::class));

        $this->createListener()->handle($domainMessage);
    }
}