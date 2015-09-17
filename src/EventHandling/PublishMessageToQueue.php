<?php

namespace Simgroep\EventSourcing\EventHandling;

use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListenerInterface;
use Simgroep\EventSourcing\Messaging\Publisher;

class PublishMessageToQueue implements EventListenerInterface
{
    /**
     * @var Publisher
     */
    private $publisher;

    /**
     * @param Publisher $publisher
     */
    public function __construct(Publisher $publisher)
    {
        $this->publisher = $publisher;
    }

    /**
     * @param DomainMessage $domainMessage
     */
    public function handle(DomainMessage $domainMessage)
    {
        $this->publisher->publish(new DomainEventStream(array($domainMessage)));
    }
}
