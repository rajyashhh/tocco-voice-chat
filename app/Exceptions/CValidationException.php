<?php

namespace App\Exceptions;

use Throwable;

class CValidationException extends \Exception
{
    protected $statusCode;

    public function __construct($message = "", $statusCode = 422, $code = 0, Throwable $previous = null)
    {
        $this->statusCode = $statusCode;
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
