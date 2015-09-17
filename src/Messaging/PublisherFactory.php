<?php

namespace Simgroep\EventSourcing\Messaging;

use Assert\Assertion;
use React\EventLoop\LoopInterface;
use Spray\Serializer\SerializerInterface;

final class PublisherFactory
{
    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @var ChannelFactory
     */
    private $channelFactory;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param LoopInterface $loop
     * @param ChannelFactory $channelFactory
     * @param SerializerInterface $serializer
     */
    public function __construct(
        LoopInterface $loop,
        ChannelFactory $channelFactory,
        SerializerInterface $serializer)
    {
        $this->loop = $loop;
        $this->channelFactory = $channelFactory;
        $this->serializer = $serializer;
    }

    /**
     * @param string $publisherType
     * @param string $exchange
     * @param string $channel
     */
    public function fanout($publisherType, $exchange, $channel)
    {
        $publisher = new $publisherType(
            $this->loop,
            $this->channelFactory,
            $this->serializer,
            $exchange,
            $channel
        );
        Assertion::isInstanceOf($publisher, AbstractFanoutPublisher::class);
        return $publisher;
    }
}
