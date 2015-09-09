<?php

namespace Simgroep\EventSourcing\Messaging;

use Broadway\Domain\DomainEventStream;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit_Framework_TestCase;
use Spray\Serializer\SerializerInterface;

class AMQPQueueTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var AMQPChannel
     */
    private $channel;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    protected function setUp()
    {
        $this->channel = $this->getMockBuilder(AMQPChannel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->serializer = $this->getMock(SerializerInterface::class);
    }

    /**
     * @return AMQPQueue
     */
    protected function createQueue()
    {
        return new AMQPQueue($this->serializer, $this->channel, 'exchange', 'key', 'queue');
    }

    public function testPublishEvent()
    {
        $message = new GenericMessage('foo', new DomainEventStream(array()));
        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with($this->identicalTo($message))
            ->will($this->returnValue(array()));
        $this->channel->expects($this->once())
            ->method('basic_publish')
            ->with(
                $this->equalTo(new AMQPMessage('[]')),
                $this->equalTo('exchange'));
        $this->createQueue()->publish($message);
    }

    public function testReceiveEvent()
    {
        $this->channel->callbacks = [true];
        $callback = function() {};

        $this->channel->expects($this->any())
            ->method('wait')
            ->will($this->returnCallback(function() {
                $this->channel->callbacks = [];
            }));
        $this->channel->expects($this->once())
            ->method('basic_consume')
            ->with(
                $this->equalTo('queue'),
                $this->equalTo(''),
                $this->equalTo(false),
                $this->equalTo(true),
                $this->equalTo(false),
                $this->equalTo(false),
                $this->identicalTo($callback));

        $this->createQueue()->receive($callback);
    }
}