<?php
namespace Simgroep\EventSourcing\EventSourcingBundle\ReadModel;

use Broadway\ReadModel\RepositoryInterface;

/**
 * Interface ClearableRepositoryInterface
 * @package Simgroep\EventSourcing\EventSourcingBundle\ReadModel
 */
interface ClearableRepositoryInterface extends RepositoryInterface
{
    public function removeAll();
}