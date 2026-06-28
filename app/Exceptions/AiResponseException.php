<?php

namespace App\Exceptions;

use Exception;

class AiResponseException extends Exception
{
    public function getHttpCode(): int
    {
        return 500;
    }
}
