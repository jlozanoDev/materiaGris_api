<?php

namespace App\Exceptions;

use Exception;

class LlmResponseException extends Exception
{
    public function getHttpCode(): int
    {
        return 500;
    }
}
