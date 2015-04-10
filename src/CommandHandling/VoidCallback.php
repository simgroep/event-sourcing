<?php

namespace Bartdezwaan\EventSourcing\CommandHandling;

class VoidCallback implements Callback
{
    /**
     * {@inheritdoc}
     */
    public function onSuccess()
    {
        
    }
    
    /**
     * {@inheritdoc}
     */
    public function onFailure()
    {
        
    }
}
