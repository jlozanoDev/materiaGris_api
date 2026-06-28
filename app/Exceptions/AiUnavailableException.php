<?php

namespace App\Exceptions;

use Exception;

class AiUnavailableException extends Exception
{
    public function getHttpCode(): int
    {
        return 503;
    }
}
