<?php

namespace Simgroep\EventSourcing\Messaging;

use RuntimeException;

class ArrayQueueRegistry implements QueueRegistry
{
    /**
     * @var array<Queue>
     */
    private $queues = array();

    /**
     * Add a queue to the registry.
     *
     * @param string $key
     * @param Queue $queue
     *
     * @return void
     */
    public function add($key, Queue $queue)
    {
        $this->queues[$key] = $queue;
    }

    /**
     * Get a queue from the registry.
     *
     * @param string $key
     *
     * @return Queue
     */
    public function get($key)
    {
        if ( ! isset($this->queues[$key])) {
            throw new RuntimeException(sprintf(
                'No queue configured for key %s',
                $key
            ));
        }
        return $this->queues[$key];
    }
}
