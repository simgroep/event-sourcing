<?php

namespace Simgroep\EventSourcing\EventSourcingBundle\Command;

use Broadway\Domain\DomainMessage;
use Generator;
use React\ChildProcess\Process;
use React\EventLoop\Factory;
use Simgroep\EventSourcing\EventSourcingBundle\Infrastructure\Replay;
use Simgroep\EventSourcing\EventSourcingBundle\ProjectorRegistry\ProjectorRegistry;
use Simgroep\EventSourcing\EventSourcingBundle\Reflector\DomainMessageReflector;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class ReplayProjectorsCommand extends ContainerAwareCommand
{
    /**
     * @var float
     */
    private $time;

    /**
     * @var string
     */
    private $perSecond;

    protected function configure()
    {
        $this
            ->setName('simgroep:eventsourcing:events:replay')
            ->setDescription('Replays events to rebuild projections');

        $this->addArgument(
            'projector',
            InputArgument::REQUIRED,
            'The name of the projector (comma separated for multiple projectors) or "all" for all projectors'
        );
        $this->addOption(
            'stream',
            'stream',
            InputOption::VALUE_REQUIRED,
            'The stream id to project'
        );

        $this->addOption(
            'interact',
            'interact',
            InputOption::VALUE_REQUIRED,
            'The stream id for interactive replaying (note: multi threading will be disabled)'
        );

        $this->addOption(
            'threads',
            'threads',
            InputOption::VALUE_REQUIRED,
            'The number of threads to dispatch (note: multi threading will be disabled when --interact is used)',
            4
        );
    }


    /**
     * Decides what to execute based on input arguments.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stream     = $input->getOption('stream');

        switch (true) {
            case $this->hasInteraction($input):
                //start replaying without multithreading
                $this->executePreparation($input, $output);
                $this->executeInteractiveStreamManager($input, $output);
                break;
            case (false === $stream):
                //multithread replaying no interaction
                $this->executePreparation($input, $output);
                $this->executeMultiThreadStreamManager($input, $output);
                break;
            default:
                //replay specific stream
                $this->executeStreamHandler($stream, $input, $output);
                break;
        }
    }

    /**
     * Prepare readmodels for projection (clears the data).
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function executePreparation(InputInterface $input, OutputInterface $output)
    {
        /** @var ProjectorRegistry $projectorRegistry */
        $projectorRegistry  = $this->getContainer()->get('sim.projector.registry');

        $projectorsToHandle  = $this->generateProjectorList(
            $input->getArgument('projector'),
            $projectorRegistry
        );

        $output->writeln("\n<info>Deleting old projections</info>");
        $this->prepareProjectorsForRebuilding($projectorRegistry, $projectorsToHandle);
        $output->writeln("--------------------------------------------------------------------------------------------------------------------------------------------");
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function executeInteractiveStreamManager(InputInterface $input, OutputInterface $output)
    {
        $streams = $this->getContainer()->get('sim.event_store.replay')->streams();

        foreach($streams as $stream) {
            $this->executeStreamHandler($stream, $input, $output);
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function executeMultiThreadStreamManager(InputInterface $input, OutputInterface $output)
    {
        /** @var Generator $streams */
        $streams = $this->getContainer()->get('sim.event_store.replay')->streams();
        $loop = Factory::create();
        $threads = 0;
        $iteration = 0;

        $loop->addPeriodicTimer(.001, function($timer) use ($loop, $streams, &$threads, &$iteration, $input, $output) {
            if ($threads >= $input->getOption('threads')) {
                return;
            }

            if ( ! $streams->valid()) {
                $loop->stop();
            }

            $threads++;
            $stream = $streams->current();
            $output->writeln(sprintf(
                '<info>Dispatching thread for event stream %s</info>',
                $stream
            ));

            $process = new Process(sprintf(
                'app/console simgroep:eventsourcing:events:replay %s --stream %s',
                $input->getArgument('projector'),
                $stream
            ));
            $process->on('exit', function($exitCode, $termSignal) use (&$threads, $output) {
                $threads--;
                if ($exitCode !== 0) {
                    $output->writeln(sprintf(
                        '<error>Process ended with code %s</error>',
                        $exitCode
                    ));
                }
            });

            $process->start($timer->getLoop());
            if ($input->getOption('verbose')) {
                $process->stdout->on('data', function($message) use ($output) {
                    $output->write($message);
                });
            }

            $output->writeln(
                sprintf(
                    '<info>Stream %s | %s streams/sec</info>',
                    str_pad(++$iteration, 10, ' ', STR_PAD_LEFT),
                    str_pad($this->perSecond($iteration), 6, ' ', STR_PAD_LEFT)
                )
            );

            $streams->next();
        });


        $loop->run();
    }

    /**
     * Project events for given $stream.
     *
     * @param string $stream
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function executeStreamHandler($stream, InputInterface $input, OutputInterface $output)
    {
        /** @var Replay $eventStore */
        /** @var ProjectorRegistry $projectorRegistry */
        $eventStore         = $this->getContainer()->get('sim.event_store.replay');
        $projectorRegistry  = $this->getContainer()->get('sim.projector.registry');

        $projectorsToHandle  = $this->generateProjectorList(
            $input->getArgument('projector'),
            $projectorRegistry
        );

        $output->writeln(sprintf(
            '<info>Projecting stream %s</info>',
            $stream
        ));
        $output->writeln("--------------------------------------------------------------------------------------------------------------------------------------------");

        $i = 0;
        $eventStore->replay($stream, function(DomainMessage $domainMessage) use ($projectorRegistry, $projectorsToHandle, $input, $output, $stream, &$i) {

            foreach ($projectorRegistry as $serviceId => $projectorMetaData) {

                if (!in_array($serviceId, $projectorsToHandle)) {
                    continue;
                }

                if ($this->hasInteraction($input) && $this->shouldInteractOnStream($input, $stream)) {
                    $this->interactOnDomainMessage($input, $output, $domainMessage);
                }

                $projectorRegistry->getProjector($serviceId)->handle($domainMessage);

                $output->writeln(
                    sprintf(
                        '<comment>- Replayed: %s: %s on projector %s</comment>',
                        $domainMessage->getId(),
                        $domainMessage->getType(),
                        $serviceId
                    )
                );

                $output->writeln("--------------------------------------------------------------------------------------------------------------------------------------------");
            }
        });

        $output->writeln("<info>Rebuilding projections completed.</info>\n");
    }

    /**
     * Calculates iterations per second.
     *
     * @param integer $iteration
     *
     * @return string
     */
    protected function perSecond($iteration)
    {
        if (null === $this->time) {
            $this->time = microtime(true);
            $this->perSecond = '...';
        }

        if (0 === $iteration % 50) {
            $this->perSecond = (string) round(1 / ((microtime(true) - $this->time) / 50), 2);
            $this->time = microtime(true);
        }

        return $this->perSecond;
    }

    /**
     * @param $name
     */
    protected function clearProjector($name)
    {
        $projectorRegistry = $this->getContainer()->get('sim.projector.registry');
        $repository = $projectorRegistry->getRepository($name);
        $repository->removeAll();
    }

    /**
     * @param $projectorArgument
     * @param ProjectorRegistry $projectorRegistry
     */
    private function generateProjectorList($projectorArgument, ProjectorRegistry $projectorRegistry)
    {
        $projectorsToHandle = array();

        switch ($projectorArgument) {
            case "all":
                $projectorsToHandle = $projectorRegistry->getAllKeys();
                break;
            case !empty($projectorArgument):
                $projectorsToHandle = explode(',', $projectorArgument);
                break;
            default:
                break;
        }

        return $projectorsToHandle;
    }

    /**
     * @param ProjectorRegistry $projectorRegistry
     * @param array $projectorsToHandle
     */
    private function prepareProjectorsForRebuilding(ProjectorRegistry $projectorRegistry, array $projectorsToHandle)
    {
        /** @var \Broadway\ReadModel\ProjectorInterface $projector */
        foreach ($projectorRegistry as $serviceId => $projector) {

            if (!in_array($serviceId, $projectorsToHandle)) {
                continue;
            }

            $this->clearProjector($serviceId);
        }
    }

    /**
     * @param InputInterface $input
     * @return bool
     */
    private function hasInteraction(InputInterface $input)
    {
        return (is_bool($input->getOption('interact'))) ? false : true;
    }

    /**
     * @param InputInterface $input
     * @param $currentStream
     * @return bool
     */
    private function shouldInteractOnStream(InputInterface $input, $currentStream)
    {
        $interactOnStreamId = $input->getOption('interact');
        return ($interactOnStreamId && $interactOnStreamId === $currentStream);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param DomainMessage $domainMessage
     */
    private function interactOnDomainMessage(InputInterface $input, OutputInterface $output, DomainMessage $domainMessage) {
        $metadata       = "";
        $payload        = "";
        $reflector      = new DomainMessageReflector($domainMessage);
        $questionHelper = new QuestionHelper();
        $question       = new ConfirmationQuestion('Continue to next playhead? (Y/n)', true);

        $reflections = $reflector->reflect(DomainMessageReflector::METADATA);
        foreach ($reflections as $property => $value) {;
            $metadata = $metadata.$property.': '.$value." ";
        }
        $reflections = $reflector->reflect(DomainMessageReflector::PAYLOAD);
        foreach ($reflections as $property => $value) {
            $payload = $payload.$property.': '.$value." ";
        }

        $table = new Table($output);
        $table->setHeaders(array("Property", "Value"))
            ->setRows(array(
                array("Id", $domainMessage->getId()),
                array("Recorded at", $domainMessage->getRecordedOn()->toString()),
                array("Playhead number", $domainMessage->getPlayhead()),
                array("Command", $domainMessage->getType()),
            ))
            ->render();
        $table = new Table($output);
        $table->setHeaders(array("Metadata"))
            ->setRows(array(
                array($metadata),
            ))
            ->render();
        $table = new Table($output);
        $table->setHeaders(array("Payload"))
            ->setRows(array(
                array($payload),
            ))
            ->render();

        if (false === $questionHelper->ask($input, $output, $question)) {
            exit("replaying stopped");
        }
    }
}
