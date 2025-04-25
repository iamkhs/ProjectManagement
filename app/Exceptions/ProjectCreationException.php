<?php

namespace App\Exceptions;

use Exception;

class ProjectCreationException extends Exception
{
    public function __construct($message = "Failed to create project", $code = 500)
    {
        parent::__construct($message, $code);
    }
}
