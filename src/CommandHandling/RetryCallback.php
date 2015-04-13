<?php

namespace Simgroep\EventSourcing\CommandHandling;

use Simgroep\EventSourcing\Repository\Exception\ConcurrencyException;
use Broadway\CommandHandling\CommandBusInterface;
use Exception;

class RetryCallback implements Callback
{
    /**
     * @var CommandBusInterface
     */
    private $commandBus;
    
    /**
     * @var string 
     */
    private $command;
    
    /**
     * @var Scheduler
     */
    private $scheduler;
    
    /**
     * @var Callback
     */
    private $callback;
    
    /**
     * @param CommandBusInterface $commandBus
     * @param object              $command
     * @param Callback            $callback
     * @param Scheduler           $scheduler
     */
    public function __construct(
        CommandBusInterface $commandBus,
        $command,
        Scheduler $scheduler,
        Callback $callback = null)
    {
        $this->commandBus = $commandBus;
        $this->command = $command;
        $this->scheduler = $scheduler;
        $this->callback = $callback;
    }
    
    /**
     * Retry the command, only if a ConcurrencyException was thrown.
     * 
     * {@inheritdoc}
     */
    public function onFailure(Exception $e)
    {
        if ( ! $e instanceof ConcurrencyException) {
            throw $e;
        }
        
        $self = $this;
        $this->scheduler->schedule(function() use ($self) {
            $self->commandBus->dispatch($self->command);
        });
    }

    /**
     * Returns onSuccess() of injected callback.
     * 
     * {@inheritdoc}
     */
    public function onSuccess()
    {
        if (null !== $this->callback) {
            return $this->callback->onSuccess();
        }
    }
}
