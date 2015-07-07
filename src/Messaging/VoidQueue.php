<?php

namespace Simgroep\EventSourcing\Messaging;

class VoidQueue implements Queue
{
    /**
     * @param QueueMessage $message
     *
     * @return void
     */
    public function publish(QueueMessage $message)
    {

    }

    /**
     * @param callable $callback
     *
     * @return void
     */
    public function receive(callable $callback)
    {

    }
}