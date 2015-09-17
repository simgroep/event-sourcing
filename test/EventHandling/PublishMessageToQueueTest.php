<?php

namespace Simgroep\EventSourcing\EventHandling;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Simgroep\EventSourcing\Messaging\Publisher;
use Simgroep\EventSourcing\Messaging\Queue;
use Simgroep\EventSourcing\TestCase;
use stdClass;

class PublishMessageToQueueTest extends TestCase
{
    private $publisher;

    protected function setUp()
    {
        $this->publisher = $this->getMock(Publisher::class);
    }

    protected function createListener()
    {
        return new PublishMessageToQueue($this->publisher);
    }

    public function testPublishDomainMessage()
    {
        $payload = new stdClass;
        $domainMessage = new DomainMessage('id', 1, new Metadata(array()), $payload, DateTime::now());

        $this->publisher->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(DomainEventStream::class));

        $this->createListener()->handle($domainMessage);
    }
}