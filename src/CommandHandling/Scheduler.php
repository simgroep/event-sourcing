<?php

namespace Bartdezwaan\EventSourcing\CommandHandling;

interface Scheduler
{
    /**
     * Schedule $callback for execution somewhere in the future.
     * 
     * @param Callable $callback
     * @return boolean
     */
    public function schedule(Callable $callback);
}
