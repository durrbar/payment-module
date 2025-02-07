<?php

namespace Modules\Payment\Exceptions;

use Exception;

class InvalidProviderException extends Exception
{
    /**
     * Create a new InvalidProviderException instance.
     */
    public function __construct($message = "Invalid payment provider", $code = 0, Exception $previous = null)
    {
        // Ensure the message is always passed
        $message = "Invalid provider: " . $message;

        // Call parent constructor to set the exception message and code
        parent::__construct($message, $code, $previous);
    }
}
