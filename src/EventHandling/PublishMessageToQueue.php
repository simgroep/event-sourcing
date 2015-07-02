<?php

namespace Simgroep\EventSourcing\EventHandling;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListenerInterface;

class PublishMessageToQueue implements EventListenerInterface
{


    /**
     * @param DomainMessage $domainMessage
     */
    public function handle(DomainMessage $domainMessage)
    {

    }
}