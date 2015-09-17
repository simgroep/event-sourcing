<?php

namespace Simgroep\EventSourcing\Messaging;

interface Consumer
{
    /**
     * @param callable $callback
     *
     * @return void
     */
    public function consume(callable $callback);
}
