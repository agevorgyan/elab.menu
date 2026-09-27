<?php

namespace App\Exceptions;

use RuntimeException;

class StorageQuotaExceededException extends RuntimeException
{
    public function __construct(
        string $message = 'Vendor storage quota exceeded.',
        public readonly ?int $limitBytes = 0,
        public readonly ?int $usedBytes = 0,
        public readonly ?int $attemptedBytes = 0,
        int $code = 422,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
