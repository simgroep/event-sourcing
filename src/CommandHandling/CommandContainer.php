<?php

namespace Simgroep\EventSourcing\CommandHandling;

use ReflectionClass;

class CommandContainer
{
    /**
     * @var array
     */
    private $availableCommands = array();

    /**
     * @param string $commandClass
     */
    public function register($commandClass)
    {
        $this->availableCommands[$this->getShortName($commandClass)] = $commandClass;
    }

    /**
     * @param string $name
     *
     * @return string|boolean
     */
    public function find($name)
    {
        if (false === isset($this->availableCommands[$name])) {
            return false;
        }

        return $this->availableCommands[$name];
    }

    /**
     * @param string $command
     *
     * @return string
     */
    private function getShortName($command)
    {
        $reflectionClass = new ReflectionClass($command);

        return $reflectionClass->getShortName();
    }
}

