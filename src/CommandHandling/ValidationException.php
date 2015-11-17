<?php

namespace Simgroep\EventSourcing\CommandHandling;

class ValidationException extends \Exception
{
    /**
     * @var mixed
     */
    private $errors;

    /**
     * @param mixed $errors
     */
    public function setErrors($errors)
    {
        $this->errors = $errors;
    }

    /**
     * @return mixed
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
