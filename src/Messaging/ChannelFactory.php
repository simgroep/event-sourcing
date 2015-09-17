<?php

namespace Simgroep\EventSourcing\Messaging;

use Bunny\Async\Client;
use Bunny\Channel;
use Exception;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use Simgroep\EventSourcing\Messaging\Exception\AsyncMessagingException;
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
     * @var array
     */
    private $options = array();

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
     * @param array $options
     */
    public function __construct(
        SerializerInterface $serializer,
        LoopInterface $loop,
        array $options)
    {
        $this->serializer = $serializer;
        $this->loop = $loop;
        $this->options = $options;
    }

    /**
     * @return PromiseInterface
     */
    public function connect()
    {
        if (null === $this->connection) {
            $client = new Client($this->loop, $this->options);
            $this->connection = $client->connect()
                ->then(
                    function(Client $client) {
                        return $client->channel();
                    },
                    function (Exception $exception) {
                        $this->loop->stop();
                        throw new AsyncMessagingException(
                            sprintf(
                                'Could not connect to rabbitmq: %s on line %s in file %s',
                                $exception->getMessage(),
                                $exception->getLine(),
                                $exception->getFile()
                            ),
                            0,
                            $exception
                        );
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
            $this->queues[$queue] = $this->connect()->then(
                function(Channel $channel) use ($exchange, $queue) {
                    return \React\Promise\all([
                        $channel,
                        $channel->queueDeclare($queue),
                        $channel->exchangeDeclare($exchange, 'fanout'),
                        $channel->queueBind($queue, $exchange),
                    ]);
                },
                function (Exception $exception) {
                    $this->loop->stop();
                    throw new AsyncMessagingException(
                        sprintf(
                            'Could not create channel and exchange: %s on line %s in file %s',
                            $exception->getMessage(),
                            $exception->getLine(),
                            $exception->getFile()
                        ),
                        0,
                        $exception
                    );
                }
            );
        }
        return $this->queues[$queue];
    }
}
