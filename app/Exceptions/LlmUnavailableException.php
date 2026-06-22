<?php

namespace App\Exceptions;

use Exception;

class LlmUnavailableException extends Exception
{
    public function getHttpCode(): int
    {
        return 503;
    }
}
