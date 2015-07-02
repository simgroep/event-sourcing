<?php

namespace Simgroep\EventSourcing\EventSourcingBundle\Command;

use Broadway\EventHandling\EventBusInterface;
use Broadway\EventStore\EventStoreInterface;
use Exception;
use Simgroep\EventSourcing\Messaging\Queue;
use Simgroep\EventSourcing\Messaging\QueueMessage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListenToQueueCommand extends Command
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
     * @param Queue $queue
     * @param EventStoreInterface $eventStore
     */
    public function __construct(
        Queue $queue,
        EventStoreInterface $eventStore,
        EventBusInterface $eventBus)
    {
        parent::__construct('queue:listen');

        $this->queue = $queue;
        $this->eventStore = $eventStore;
        $this->eventBus = $eventBus;
    }

    protected function configure()
    {
        $this->addArgument('queue', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(sprintf('<info>Listening to queue:</info> %s', $input->getArgument('queue')));

        $this->queue->receive(function(QueueMessage $message) use ($output) {
            try {
                $output->writeln(sprintf('<info>Handling message with id:</info> %s', $message->getId()));
                $this->eventStore->append($message->getId(), $message->getStream());
                $this->eventBus->publish($message->getStream());
            } catch (Exception $e) {
                $output->writeln(
                    '<error>Could not handle message:</error> %s in file %s on line %s: %s',
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine(),
                    $e->getTraceAsString()
                );
            }
        });
    }
}