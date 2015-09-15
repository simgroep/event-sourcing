<?php

namespace Simgroep\EventSourcing\Messaging;

use Exception;
use Simgroep\EventSourcing\Messaging\Exception\AsyncMessagingException;

class ExceptionErrorHandler implements AsyncErrorHandler
{
    /**
     * @param string $message
     * @param Exception $exception
     *
     * @return void
     */
    public function handle($message, Exception $exception)
    {
        throw new AsyncMessagingException(
            sprintf(
                '%s: %s on line %s in file %s',
                $message,
                $exception->getMessage(),
                $exception->getLine(),
                $exception->getFile()
            ),
            0,
            $exception
        );
    }
}
