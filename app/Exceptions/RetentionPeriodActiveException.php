<?php

namespace App\Exceptions;

use Carbon\CarbonInterface;
use RuntimeException;

class RetentionPeriodActiveException extends RuntimeException
{
    public function __construct(
        string $message = 'Vendor data retention period is still active. Deletion cannot proceed.',
        public readonly ?CarbonInterface $retentionEndsAt = null,
        int $code = 422,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
