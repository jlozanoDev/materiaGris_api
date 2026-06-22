<?php

namespace App\Exceptions;

use Exception;

class LlmTimeoutException extends Exception
{
    protected $message = 'LLM request timed out';

    public function getHttpCode(): int
    {
        return 500;
    }
}
