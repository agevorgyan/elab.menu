<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidStoragePathException extends RuntimeException
{
    public function __construct(
        string $message = 'Invalid or unauthorized storage path.',
        int $code = 403,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
