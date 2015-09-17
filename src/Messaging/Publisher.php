<?php

namespace Simgroep\EventSourcing\Messaging;

interface Publisher
{
    /**
     * @param mixed $payload
     *
     * @return void
     */
    public function publish($payload);
}
