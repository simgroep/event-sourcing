<?php

namespace Simgroep\EventSourcing\Messaging;

use Bunny\Channel;
use Exception;
use React\EventLoop\LoopInterface;
use Simgroep\EventSourcing\Messaging\Exception\AsyncMessagingException;
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
            ->then(
                function($r) use ($message) {
                    /** @var Channel $channel */
                    $channel = $r[0];

                    return \React\Promise\all([
                        $channel->publish(
                            json_encode(
                                $this->serializer->serialize(
                                    $message
                                )
                            ),
                            [],
                            $this->exchange
                        ),
                    ]);
                },
                function(Exception $exception) {
                    $this->loop->stop();
                    var_dump(sprintf(
                        'Could not publish message: %s on line %s in file %s',
                        $exception->getMessage(),
                        $exception->getLine(),
                        $exception->getFile()
                    ));
//                    throw new AsyncMessagingException(
//                        sprintf(
//                            'Could not publish message: %s on line %s in file %s',
//                            $exception->getMessage(),
//                            $exception->getLine(),
//                            $exception->getFile()
//                        ),
//                        0,
//                        $exception
//                    );
                }
            )
            ->then(
                function() {
                    $this->loop->stop();
                },
                function(Exception $exception) {
                    $this->loop->stop();
                    var_dump(sprintf(
                        'Could not publish message: %s on line %s in file %s',
                        $exception->getMessage(),
                        $exception->getLine(),
                        $exception->getFile()
                    ));
                }
            );

        $this->loop->run();
    }
}
