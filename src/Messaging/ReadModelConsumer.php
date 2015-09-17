<?php

namespace Simgroep\EventSourcing\Messaging;

class ReadModelConsumer extends AbstractFanoutConsumer
{
    /**
     * @param callable $callback
     *
     * @return void
     */
    public function consume(callable $callback)
    {
        $this->consumeMessage(function(ReadModelMessage $message) use ($callback) {
            $callback($message->getPayload());
        });
    }
}
