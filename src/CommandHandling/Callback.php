<?php

namespace Simgroep\EventSourcing\CommandHandling;

use Exception;

interface Callback
{
    /**
     * Triggered if command dispatching succeeded.
     * 
     * @return void
     */
    public function onSuccess();
    
    /**
     * Triggered if command dispatching failed.
     * 
     * @param Exception $e
     * 
     * @return void
     */
    public function onFailure(Exception $e);
}
