<?php

namespace Simgroep\EventSourcing\Messaging;

use Broadway\Domain\DomainEventStreamInterface;

interface QueueMessage
{
    /**
     * @return string
     */
    public function getId();

    /**
     * @return DomainEventStreamInterface
     */
    public function getStream();
}
