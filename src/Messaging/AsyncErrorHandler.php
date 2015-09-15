<?php

namespace Simgroep\EventSourcing\Messaging;

use Exception;

interface AsyncErrorHandler
{
    /**
     * @param string $message
     * @param Exception $exception
     *
     * @return void
     */
    public function handle($message, Exception $exception);
}
