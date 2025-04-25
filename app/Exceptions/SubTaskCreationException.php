<?php

namespace App\Exceptions;

use Exception;

class SubTaskCreationException extends Exception
{
    public function __construct($message = "Failed to create SubTask", $code = 500)
    {
        parent::__construct($message, $code);
    }
}
