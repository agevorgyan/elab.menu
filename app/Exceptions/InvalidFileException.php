<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidFileException extends RuntimeException
{
    public function __construct(
        string $message = 'Uploaded file failed security validation.',
        int $code = 422,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
