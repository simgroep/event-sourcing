<?php

namespace Simgroep\EventSourcing\CommandHandling;

use Broadway\CommandHandling\CommandBusInterface;
use Exception;

class CommandGateway implements Gateway
{
    /**
     * @var CommandBusInterface
     */
    private $commandBus;
    
    /**
     * @var Scheduler 
     */
    private $scheduler;
    
    /**
     * 
     * @param CommandBusInterface $commandBus
     * @param Scheduler $scheduler
     */
    public function __construct(CommandBusInterface $commandBus, Scheduler $scheduler)
    {
        $this->commandBus = $commandBus;
        $this->scheduler = $scheduler;
    }
    
    /**
     * {@inheritdoc}
     */
    public function send($command, Callback $callback = null)
    {
        $retry = new RetryCallback($this->commandBus, $command, $this->scheduler, $callback);
        
        try {
            $this->commandBus->dispatch($command);
        } catch (Exception $e) {
            return $retry->onFailure($e);
        }
        
        return $retry->onSuccess();
    }
}
