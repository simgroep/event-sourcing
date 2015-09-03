<?php

namespace Simgroep\EventSourcing\EventSourcingBundle\Command;

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
            'The stream id for interactive replaying'
        );

        $this->addOption(
            'threads',
            'threads',
            InputOption::VALUE_REQUIRED,
            'The number of threads to dispatch',
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
        $stream = $input->getOption('stream');

        if ( ! $stream) {
            $this->executePreparation($input, $output);
            $this->executeStreamManager($input, $output);
        } else {
            $this->executeStreamHandler($stream, $input, $output);
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
     * Dispatches projector thread per event stream.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function executeStreamManager(InputInterface $input, OutputInterface $output)
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

            if ($this->shouldInteractOnStream($input, $stream)) {
                $this->interactiveStream($input, $output, $stream);
            }

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
        $eventStore->replay($stream, function($domainMessage) use ($projectorRegistry, $projectorsToHandle, $output, &$i) {
            /** @var \Broadway\Domain\DomainMessage $domainMessage */
            /** @var \Broadway\ReadModel\ProjectorInterface $projector */
            foreach ($projectorRegistry as $serviceId => $projector) {

                if (!in_array($serviceId, $projectorsToHandle)) {
                    continue;
                }

                $projector->handle($domainMessage);
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
        $repository = $this->locateRepository($name);
        $repository->removeAll();
    }

    /**
     * @param $name
     * @return object
     */
    private function locateRepository($name)
    {
        return $this->getContainer()->get(
            sprintf('sim.read_model.repository.%s',$name)
        );
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
     * @param $input
     * @param $currentStream
     * @return bool
     */
    private function shouldInteractOnStream($input, $currentStream)
    {
        $interactOnStreamId = $input->getOption('interact');
        return ($interactOnStreamId && $interactOnStreamId === $currentStream);
    }

    private function interactiveStream($input, $output, $currentStream) {
        /** @var Replay $eventStore */
        $eventStore         = $this->getContainer()->get('sim.event_store.replay');
        $eventStore->replay($currentStream, function($stream) use ($input, $output) {
            $metadata       = "";
            $payload        = "";
            $reflector      = new DomainMessageReflector($stream);
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
                    array("Id", $stream->getId()),
                    array("Recorded at", $stream->getRecordedOn()->toString()),
                    array("Playhead number", $stream->getPlayhead()),
                    array("Command", $stream->getType()),
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
        });
    }
}
