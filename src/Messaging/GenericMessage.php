<?php

namespace Simgroep\EventSourcing\Messaging;

use Broadway\Domain\DomainEventStreamInterface;

class GenericMessage implements QueueMessage
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var DomainEventStreamInterface
     */
    private $stream;

    /**
     * @param string                     $id
     * @param DomainEventStreamInterface $stream
     */
    public function __construct($id, DomainEventStreamInterface $stream)
    {
        $this->id = $id;
        $this->stream = $stream;
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
    public function getStream()
    {
        return $this->stream;
    }

}