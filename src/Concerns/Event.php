<?php 

namespace Citrus\Concerns;

abstract class Event
{   
    
    /**
     * Create a new Event.
     *
     * @param array $args
     */
    public function __construct(array $args)
    {
        $this->args = $args;
    }

}
