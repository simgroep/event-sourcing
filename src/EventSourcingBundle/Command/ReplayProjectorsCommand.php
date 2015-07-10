<?php

namespace Simgroep\EventSourcing\EventSourcingBundle\Command;

use SIM\SettingsBundle\ProjectorRegistry\ProjectorRegistry;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReplayProjectorsCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName('config:replay:events')
            ->setDescription('Replays events to rebuild read models')
        ;

        $this->addArgument(
            'projector',
            InputArgument::REQUIRED,
            'The name of the projector (comma separated for multiple projectors) or "all" for all projectors'
        );
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \SIM\SettingsBundle\Infrastructure\Replay $eventStore */
        /** @var \SIM\SettingsBundle\ProjectorRegistry\ProjectorRegistry $projectorRegistry */
        $eventStore         = $this->getContainer()->get('sim.event_store.replay');
        $projectorRegistry  = $this->getContainer()->get('sim.projector.registry');


        $projectorsToHandle  = $this->generateProjectorList(
            $input->getArgument('projector'),
            $projectorRegistry
        );

        $this->prepareProjectorsForRebuilding($projectorRegistry, $projectorsToHandle);

        $eventStore->replay(function($domainMessage) use ($projectorRegistry, $projectorsToHandle, $output) {
            /** @var \Broadway\Domain\DomainMessage $domainMessage */
            /** @var \Broadway\ReadModel\ProjectorInterface $projector */
            foreach ($projectorRegistry as $serviceId => $projector) {

                if (!in_array($serviceId, $projectorsToHandle)) {
                    continue;
                }
                $projector->handle($domainMessage);
                $output->writeln(
                    sprintf(
                        'Replayed: %s: %s on projector %s',
                        $domainMessage->getId(),
                        $domainMessage->getType(),
                        $serviceId
                    )
                );
            }
        });

        $output->writeln('Rebuild projectors completed.');
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
}