<?php

namespace Bartdezwaan\EventSourcing\EventHandling;

use Broadway\Domain\DomainEventStreamInterface;
use Broadway\EventHandling\EventBusInterface;
use Broadway\EventHandling\EventListenerInterface;

class RabbitMQEventBus implements EventBusInterface
{
    /**
     * {@inheritDoc}
     */
    public function subscribe(EventListenerInterface $eventListener)
    {
        $this->eventListeners[] = $eventListener;
    }

    /**
     * {@inheritDoc}
     */
    public function publish(DomainEventStreamInterface $domainMessages)
    {
    }
}

