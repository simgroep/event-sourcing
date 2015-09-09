<?php

namespace Simgroep\EventSourcing\EventSourcingBundle\Command;

use Broadway\EventHandling\EventBusInterface;
use Broadway\EventStore\EventStoreInterface;
use Exception;
use Simgroep\EventSourcing\Messaging\Queue;
use Simgroep\EventSourcing\Messaging\QueueMessage;
use Simgroep\EventSourcing\Messaging\QueueRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListenToQueueCommand extends Command
{
    /**
     * @var Queue
     */
    private $queueRegistry;

    /**
     * @var EventStoreInterface
     */
    private $eventStore;

    /**
     * @param QueueRegistry       $queue
     * @param EventStoreInterface $eventStore
     * @param EventBusInterface   $eventBus
     */
    public function __construct(
        QueueRegistry $queue,
        EventStoreInterface $eventStore,
        EventBusInterface $eventBus)
    {
        parent::__construct('queue:listen');

        $this->queueRegistry = $queue;
        $this->eventStore = $eventStore;
        $this->eventBus = $eventBus;
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this->addArgument('queue', InputArgument::REQUIRED);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(sprintf('<info>Listening to queue:</info> %s', $input->getArgument('queue')));

        $this->queueRegistry->get($input->getArgument('queue'))->receive(function(QueueMessage $message) use ($output) {
            try {
                $output->writeln(sprintf('<info>Handling message with id:</info> %s', $message->getId()));
                $this->eventStore->append($message->getId(), $message->getPayload());
                $this->eventBus->publish($message->getPayload());
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
