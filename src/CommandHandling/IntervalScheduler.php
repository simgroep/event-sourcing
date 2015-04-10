<?php

namespace Bartdezwaan\EventSourcing\CommandHandling;

use Bartdezwaan\EventSourcing\Repository\Exception\ConcurrencyException;

class IntervalScheduler implements Scheduler
{
    /**
     * @var integer
     */
    private $interval;
    
    /**
     * @var integer
     */
    private $retriesLeft;
    
    /**
     * Keeps retrying given callback each $interval untill there are no retries
     * left.
     * 
     * @param integer $interval
     * @param integer $retriesLeft
     */
    public function __construct($interval, $retriesLeft)
    {
        $this->interval = $interval;
        $this->retriesLeft = $retriesLeft;
    }
    
    /**
     * Schedule $callback to be executed at configured interval untill the
     * timeout is reached.
     * 
     * @param callable $callback
     * @return boolean
     */
    public function schedule(callable $callback)
    {
        usleep($this->interval);
        
        try {
            return $callback();
        } catch (ConcurrencyException $e) {
            if (1 === $this->retriesLeft) {
                throw $e;
            }
            
            return (new IntervalScheduler($this->interval, $this->retriesLeft - 1))
                ->schedule($callback);
        }
    }
}
