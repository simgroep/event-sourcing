<?php

namespace Simgroep\EventSourcing\Messaging;

final class DomainEventStreamPublisher extends AbstractFanoutPublisher
{
    /**
     * @param mixed $payload
     *
     * @return void
     */
    public function publish($payload)
    {
        $this->publishMessage(new DomainEventStreamMessage($payload));
    }
}
