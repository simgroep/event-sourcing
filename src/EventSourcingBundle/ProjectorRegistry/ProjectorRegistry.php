<?php

namespace Simgroep\EventSourcing\EventSourcingBundle\ProjectorRegistry;

use ArrayAccess;
use ArrayIterator;
use Broadway\ReadModel\ProjectorInterface;
use IteratorAggregate;
use SIM\SettingsBundle\ProjectorRegistry\Exceptions\DuplicateProjectorException;


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

    public function getAllKeys()
    {
        return array_keys($this->projectors);
    }

    public function offsetExists($offset)
    {
        return isset($this->projectors[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($this->projectors[$offset]) ? $this->projectors[$offset] : null;
    }


    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->projectors[] = $value;
        } else {
            $this->projectors[$offset] = $value;
        }
    }

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