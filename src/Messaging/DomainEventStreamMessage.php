<?php

namespace Simgroep\EventSourcing\Messaging;

use Broadway\Domain\DomainEventStreamInterface;
use Rhumsaa\Uuid\Uuid;

class DomainEventStreamMessage implements QueueMessage
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var DomainEventStreamInterface
     */
    private $payload;

    /**
     * @param DomainEventStreamInterface $stream
     */
    public function __construct(DomainEventStreamInterface $stream)
    {
        $this->id = (string) Uuid::uuid4();
        $this->payload = $stream;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return DomainEventStreamInterface
     */
    public function getPayload()
    {
        return $this->payload;
    }
}
