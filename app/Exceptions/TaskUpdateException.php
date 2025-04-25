<?php

namespace App\Exceptions;

use Exception;

class TaskUpdateException extends Exception
{
    public function __construct($message = "Failed to update task", $code = 500)
    {
        parent::__construct($message, $code);
    }
}
