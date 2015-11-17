<?php

namespace Simgroep\EventSourcing\CommandHandling;

interface Validator
{
    /**
     * Validate command.
     * Return an array with errors.
     *
     * @param object $command
     *
     * @return array
     */
    public function validate($command);
}

