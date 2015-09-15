<?php

namespace Simgroep\EventSourcing\Messaging;

use Broadway\ReadModel\ReadModelInterface;
use Rhumsaa\Uuid\Uuid;

class ReadModelMessage implements QueueMessage
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var ReadModelInterface
     */
    private $payload;

    /**
     * @param ReadModelInterface $readModel
     */
    public function __construct(ReadModelInterface $readModel)
    {
        $this->id = (string) Uuid::uuid4();
        $this->payload = $readModel;
    }

    /**
     * @return Uuid
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return ReadModelInterface
     */
    public function getPayload()
    {
        return $this->payload;
    }
}
