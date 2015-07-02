<?php

namespace Simgroep\EventSourcing\Messaging;

interface Queue
{
    /**
     * @param QueueMessage $message
     *
     * @return void
     */
    public function publish(QueueMessage $message);

    /**
     * @param callable $callback
     *
     * @return void
     */
    public function receive(callable $callback);
}
