<?php

namespace Simgroep\EventSourcing\EventSourcingBundle\Command;

use Broadway\Domain\DomainEventStream;
use Broadway\EventHandling\EventBusInterface;
use Broadway\EventStore\EventStoreInterface;
use Simgroep\EventSourcing\Messaging\GenericMessage;
use Simgroep\EventSourcing\Messaging\Queue;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class ListenToQueueCommandTest extends WebTestCase
{
    /**
     * @var Queue
     */
    private $queue;

    /**
     * @var EventStoreInterface
     */
    private $eventStore;

    /**
     * @var EventBusInterface
     */
    private $eventBus;

    protected function setUp()
    {
        $this->queue = $this->getMock(Queue::class);
        $this->eventStore = $this->getMock(EventStoreInterface::class);
        $this->eventBus = $this->getMock(EventBusInterface::class);
    }

    protected function createCommand()
    {
        return new ListenToQueueCommand(
            $this->queue,
            $this->eventStore,
            $this->eventBus
        );
    }

    public function testItListensToAQueue()
    {
        $message = new GenericMessage('foo', new DomainEventStream(array()));
        $this->queue->expects($this->once())
            ->method('receive')
            ->will($this->returnCallback(function($callback) use ($message) {
                return $callback($message);
            }));
        $this->eventStore->expects($this->once())
            ->method('append')
            ->with(
                $this->equalTo('foo'),
                $this->identicalTo($message->getStream()));
        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with($this->identicalTo($message->getStream()));

        $input = new ArrayInput(array(
            'queue' => 'foo'
        ));
        $output = new NullOutput();

        $command = $this->createCommand();
        $command->run($input, $output);
    }
}
