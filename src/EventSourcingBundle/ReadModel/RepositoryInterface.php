<?php
namespace Simgroep\EventSourcing\EventSourcingBundle\ReadModel;

use Broadway\ReadModel\RepositoryInterface as BroadwayRepositoryInterface;

interface RepositoryInterface extends BroadwayRepositoryInterface
{
    public function removeAll();
}