<?php

namespace Simgroep\EventSourcing\CommandHandling;

use ReflectionClass;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CommandFactory
{
    /**
     * @var CommandContainer
     */
    private $commandContainer;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @param CommandContainer   $commandContainer
     * @param ValidatorInterface $validator
     */
    public function __construct(CommandContainer $commandContainer, ValidatorInterface $validator)
    {
        $this->commandContainer = $commandContainer;
        $this->validator = $validator;
    }

    /**
     * @param array $commandData
     */
    public function create(array $commandData)
    {
        $commandClass = $this->commandContainer->find(key($commandData));

        $reflClass = new ReflectionClass($commandClass);
        $command = $reflClass->newInstanceWithoutConstructor();

        foreach (reset($commandData) as $key => $value) {
            try {
                $reflectionProperty = $reflClass->getProperty($key);
                $reflectionProperty->setAccessible(true);
                $reflectionProperty->setValue($command, $value);
            } catch(\ReflectionException $e) {
                continue;
            }
        }

        $errors = $this->validateCommand($command);
        if (count($errors) > 0) {
            $exception =  new ValidationException('Invalid command given');
            $exception->setErrors($errors);

            throw $exception;
        }

        return $command;
    }

    /**
     * @param mixed $command
     *
     * @return array
     */
    private function validateCommand($command)
    {
        if ($this->validator) {
            return $this->validator->validate($command);
        }

        return null;
    }
}

