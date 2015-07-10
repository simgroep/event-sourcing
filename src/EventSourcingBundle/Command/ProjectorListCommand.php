<?php

namespace Simgroep\EventSourcing\EventSourcingBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProjectorListCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName('config:projectors:list')
            ->setDescription('List all projectors')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \SIM\SettingsBundle\ProjectorRegistry\ProjectorRegistry $projectorRegistry */
        $projectorRegistry  = $this->getContainer()->get('sim.projector.registry');
        $projectorsIds      = $projectorRegistry->getAllKeys();

        $output->writeln("The following projectors are available for replaying");
        foreach ($projectorsIds as $projectorsId) {
            $output->writeln(
                sprintf("projector id: %s", $projectorsId)
            );
        }
    }
}