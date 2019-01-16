<?php

namespace Async\Coroutine;

/**
 * Interface representing something that can be cancelled
 */
interface Cancelable 
{
    /**
     * Indicates if the cancelable object has been cancelled
     * 
     * @return boolean
     */
    public function isCancelled();
    
    /**
     * Cancels the cancelable object
     */
    public function cancel();
}
