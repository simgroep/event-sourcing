<?php
namespace Simgroep\EventSourcing\EventSourcingBundle\ReadModel;

interface RepositoryInterface extends \Broadway\ReadModel\RepositoryInterface
{
    public function removeAll();
}