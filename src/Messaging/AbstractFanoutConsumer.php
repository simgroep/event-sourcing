<?php

namespace Simgroep\EventSourcing\Messaging;

use Bunny\Channel;
use Bunny\Message;
use Exception;
use React\EventLoop\LoopInterface;
use Simgroep\EventSourcing\Messaging\Exception\AsyncMessagingException;
use Spray\Serializer\SerializerInterface;

abstract class AbstractFanoutConsumer implements Consumer
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
     * @param callable $callback
     *
     * @return void
     */
    protected function consumeMessage(callable $callback)
    {
        $this->channelFactory->fanout($this->exchange, $this->channel)
            ->then(
                function($r) use ($callback) {
                    /** @var Channel $channel */
                    $channel = $r[0];
                    return $channel->consume(function(Message $message) use ($callback) {
                        $data = json_decode($message->content, true);
                        $callback($this->serializer->deserialize(null, $data));
                    }, $this->channel, '', false, true);
                },
                function(Exception $exception) {
                    $this->loop->stop();
                    throw new AsyncMessagingException(
                        sprintf(
                            'Could not consume message: %s on line %s in file %s',
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
}
