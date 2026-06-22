<?php

namespace App\Exceptions;

use Exception;

class TemplateNotFoundException extends Exception
{
    public function getHttpCode(): int
    {
        return 400;
    }
}
