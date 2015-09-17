<?php

namespace Simgroep\EventSourcing\Messaging;

use Broadway\ReadModel\ReadModelInterface;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use Spray\Serializer\SerializerInterface;

final class ReadModelPublisher extends AbstractFanoutPublisher
{
    /**
     * @param ReadModelInterface $payload
     *
     * @return PromiseInterface
     */
    public function publish($payload)
    {
        $this->publishMessage(new ReadModelMessage($payload));
    }
}
