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
            ->setName('simgroep:eventsourcing:projectors:list')
            ->setDescription('List all projectors')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \Simgroep\EventSourcing\EventSourcingBundle\ProjectorRegistry\ProjectorRegistry $projectorRegistry */
        $projectorRegistry  = $this->getContainer()->get('sim.projector.registry');
        $projectorsIds      = $projectorRegistry->getAllKeys();

        if (empty($projectorsIds)) {
            $output->writeln("\n<comment>No projectors found for rebuilding.</comment>");
        } else {
            $output->writeln("\n<info>The following projectors are available for rebuilding:</info>");
        }

        foreach ($projectorsIds as $projectorsId) {
            $output->writeln(
                sprintf("<comment>- %s</comment>", $projectorsId)
            );
        }

        $output->writeln("");
    }
}