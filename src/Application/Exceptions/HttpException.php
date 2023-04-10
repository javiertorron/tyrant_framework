<?php

namespace Tyrant\Exceptions;
use Exception;

class HttpException extends Exception
{
    public function __construct(string $message = "Internal Server Error", int $code = 500, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}