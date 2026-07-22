<?php

namespace App\Exceptions;

class InsufficientStockException extends HttpException
{
    public function __construct(string $message = 'Stock insuffisant')
    {
        parent::__construct(409, $message);
    }
}
