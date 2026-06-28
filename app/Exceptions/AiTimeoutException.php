<?php

namespace App\Exceptions;

use Exception;

class AiTimeoutException extends Exception
{
    protected $message = 'AI request timed out';

    public function getHttpCode(): int
    {
        return 500;
    }
}
