<?php

namespace Simgroep\EventSourcing\Messaging;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Simgroep\EventSourcing\TestCase;
use Spray\Serializer\SerializerInterface;

class AMQPQueueFactoryTest extends TestCase
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var AMQPStreamConnection
     */
    private $connection;

    /**
     * @var AMQPChannel
     */
    private $channel;

    protected function setUp()
    {
        $this->serializer = $this->getMock(SerializerInterface::class);
        $this->connection = $this->getMockBuilder(AMQPStreamConnection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->channel = $this->getMockBuilder(AMQPChannel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->connection->expects($this->any())
            ->method('channel')
            ->will($this->returnValue($this->channel));
    }

    /**
     * @return AMQPQueueFactory
     */
    protected function createFactory()
    {
        return new AMQPQueueFactory(
            $this->serializer,
            $this->connection
        );
    }

    public function testBuildQueue()
    {
        $this->assertInstanceOf(
            AMQPQueue::class,
            $this->createFactory()->build('exchange', 'routingKey', 'queue')
        );
    }
}