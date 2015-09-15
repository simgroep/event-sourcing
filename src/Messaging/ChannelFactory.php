<?php

namespace Simgroep\EventSourcing\Messaging;

use Bunny\Async\Client;
use Bunny\Channel;
use Bunny\Message;
use Exception;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use Spray\Serializer\SerializerInterface;

final class ChannelFactory
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @var PromiseInterface
     */
    private $connection;

    /**
     * @var array<PromiseInterface>
     */
    private $queues = array();

    /**
     * @param SerializerInterface $serializer
     * @param LoopInterface $loop
     * @param AsyncErrorHandler $errorHandler
     * @param array $options
     */
    public function __construct(
        SerializerInterface $serializer,
        LoopInterface $loop,
        AsyncErrorHandler $errorHandler,
        array $options)
    {
        $this->serializer = $serializer;
        $this->loop = $loop;
        $this->errorHandler = $errorHandler;
    }

    /**
     * @return PromiseInterface
     */
    public function connect()
    {
        if (null === $this->connection) {
            $client = new Client($this->loop);
            $this->connection = $client->connect()
                ->then(
                    function(Client $client) {
                        return $client->channel();
                    },
                    function (Exception $e) {
                        $this->errorHandler->handle('Could not connect to rabbitmq', $e);
                    }
                );
        }
        return $this->connection;
    }

    /**
     * Create a queue with fanout exchange.
     *
     * @param string $exchange
     * @param string $queue
     *
     * @return PromiseInterface
     */
    public function fanout($exchange, $queue)
    {
        if ( ! isset($this->queues[$queue])) {
            $this->connect()->then(
                function(Channel $channel) use ($exchange, $queue) {
                    return \React\Promise\all([
                        $channel,
                        $channel->queueDeclare($queue),
                        $channel->exchangeDeclare($exchange, 'fanout'),
                        $channel->queueBind($queue, $exchange),
                    ]);
                },
                function (Exception $e) {
                    $this->errorHandler->handle('Could not create channel and exchange', $e);
                }
            );
        }
        return $this->queues[$queue];
    }
}
