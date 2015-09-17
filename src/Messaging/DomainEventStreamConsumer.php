<?php

namespace Simgroep\EventSourcing\Messaging;

class DomainEventStreamConsumer extends AbstractFanoutConsumer
{
    /**
     * @param callable $callback
     *
     * @return void
     */
    public function consume(callable $callback)
    {
        $this->consumeMessage(function(DomainEventStreamMessage $message) use ($callback) {
            $callback($message->getPayload());
        });
    }
}
