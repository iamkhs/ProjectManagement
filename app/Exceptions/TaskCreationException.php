<?php

namespace App\Exceptions;

use Exception;

class TaskCreationException extends Exception
{
    public function __construct($message = "Failed to create Task", $code = 500)
    {
        parent::__construct($message, $code);
    }
}
