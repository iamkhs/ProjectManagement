<?php

namespace App\Exceptions;

use Exception;

class SubTaskUpdateException extends Exception
{
    public function __construct($message = "Failed to update SuTask", $code = 500)
    {
        parent::__construct($message, $code);
    }
}
