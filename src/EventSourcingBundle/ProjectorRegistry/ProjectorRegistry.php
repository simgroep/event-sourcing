<?php

namespace Simgroep\EventSourcing\EventSourcingBundle\ProjectorRegistry;

use ArrayAccess;
use ArrayIterator;
use Broadway\ReadModel\ProjectorInterface;
use IteratorAggregate;
use Simgroep\EventSourcing\EventSourcingBundle\ProjectorRegistry\Exceptions\DuplicateProjectorException;


/**
 * Class ProjectorRegistry
 * @package SIM\SettingsBundle\ProjectorRegistry
 */
class ProjectorRegistry implements ArrayAccess, IteratorAggregate
{

    /**
     * @var array
     */
    private $projectors = array();

    /**
     * @param ProjectorInterface $projector
     */
    public function addProjector(ProjectorInterface $projector, $key = null)
    {
        foreach ($this->projectors as $index => $existingProjector) {
            if ($projector === $existingProjector) {
                throw new DuplicateProjectorException(
                    sprintf("Projector: '%s' already exists in Registry", get_class($projector)));
            }
        }

        $this->projectors[$key] = $projector;
    }

    /**
     * @return array
     */
    public function getAllKeys()
    {
        return array_keys($this->projectors);
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->projectors[$offset]);
    }

    /**
     * @param mixed $offset
     * @return null
     */
    public function offsetGet($offset)
    {
        return isset($this->projectors[$offset]) ? $this->projectors[$offset] : null;
    }


    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->projectors[] = $value;
        } else {
            $this->projectors[$offset] = $value;
        }
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->projectors[$offset]);
    }

    /**
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->projectors);
    }


}