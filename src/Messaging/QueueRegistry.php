<?php

namespace Simgroep\EventSourcing\Messaging;

interface QueueRegistry
{
    /**
     * Add a queue to the registry.
     *
     * @param string $key
     * @param Queue $queue
     *
     * @return void
     */
    public function add($key, Queue $queue);

    /**
     * Get a queue from the registry.
     *
     * @param string $key
     *
     * @return Queue
     */
    public function get($key);
}