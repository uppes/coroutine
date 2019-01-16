<?php

namespace Async\Coroutine\Tasks;

use Exception;

/**
 * Exception representing multiple task failures
 */
class MultipleFailureException extends Exception 
{
    protected $exceptions = [];
    
    /**
     * Create a new multiple failure exception from the given exceptions
     * 
     * @param array $exceptions
     */
    public function __construct(array $exceptions) 
	{
        $this->exceptions = $exceptions;
        
        parent::__construct(
            count($exceptions) . " tasks failed - use getFailures to access individual failures"
        );
    }
    
    /**
     * Get the array of exceptions that caused this exception
     * 
     * @return \Exception[]
     */
    public function getFailures() 
	{
        return $this->exceptions;
    }
}
