<?php

namespace Simgroep\EventSourcing\Messaging;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use Spray\Serializer\SerializerInterface;

class AMQPQueue implements Queue
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var AMQPChannel
     */
    private $channel;

    /**
     * @var string
     */
    private $exchange;

    /**
     * @var string
     */
    private $routingKey;

    /**
     * @param AMQPChannel         $channel
     * @param SerializerInterface $serializer
     */
    public function __construct(
        SerializerInterface $serializer,
        AMQPChannel $channel,
        $exchange = '',
        $routingKey = '',
        $queue = '')
    {
        $this->serializer = $serializer;
        $this->channel = $channel;
        $this->exchange = (string) $exchange;
        $this->routingKey = (string) $routingKey;
        $this->queue = (string) $queue;
    }

    /**
     * @param QueueMessage $message
     *
     * @return void
     */
    public function publish(QueueMessage $message)
    {
        $this->channel->basic_publish(
            new AMQPMessage($this->serializer->serialize($message)),
            $this->exchange,
            $this->routingKey
        );
    }

    /**
     * @param callable $callback
     *
     * @return void
     */
    public function receive(callable $callback)
    {
        $this->channel->basic_consume($this->queue, '', false, true, false, false, $callback);
        while(count($this->channel->callbacks)) {
            $this->channel->wait();
        }
    }
}