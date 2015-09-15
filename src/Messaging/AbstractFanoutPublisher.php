<?php

namespace Simgroep\EventSourcing\Messaging;

use Bunny\Channel;
use React\EventLoop\LoopInterface;
use Spray\Serializer\SerializerInterface;

abstract class AbstractFanoutPublisher implements Publisher
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
     * @var string
     */
    private $exchange;

    /**
     * @var string
     */
    private $channel;

    /**
     * @param LoopInterface $loop
     * @param ChannelFactory $channelFactory
     * @param SerializerInterface $serializer
     * @param string $exchange
     * @param string $channel
     */
    public function __construct(
        LoopInterface $loop,
        ChannelFactory $channelFactory,
        SerializerInterface $serializer,
        $exchange,
        $channel)
    {
        $this->loop = $loop;
        $this->channelFactory = $channelFactory;
        $this->serializer = $serializer;
        $this->exchange = (string) $exchange;
        $this->channel = (string) $channel;
    }

    /**
     * @param QueueMessage $message
     *
     * @return void
     */
    protected function publishMessage(QueueMessage $message)
    {
        $this->channelFactory->fanout($this->exchange, $this->channel)
            ->then(function($r) use ($message) {
                /** @var Channel $channel */
                $channel = $r[0];

                return \React\Promise\all([
                    $channel->publish(
                        json_encode(
                            $this->serializer->serialize(
                                $message
                            )
                        ),
                        []
                    ),
                ]);
            })
            ->then(function() {
                $this->loop->stop();
            });

        $this->loop->run();
    }
}