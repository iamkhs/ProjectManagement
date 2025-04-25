<?php

namespace App\Exceptions;

use Exception;

class ProjectUpdateException extends Exception
{
    public function __construct($message = "Failed to update project", $code = 500)
    {
        parent::__construct($message, $code);
    }
}
