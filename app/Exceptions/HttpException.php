<?php

namespace App\Exceptions;

use Exception;

class HttpException extends Exception
{
    public function __construct(public readonly int $status, string $message)
    {
        parent::__construct($message);
    }
}
