<?php

namespace App\Jobs\Contracts;

interface TenantJobInterface
{
    /**
     * Get the tenant vendor ID.
     */
    public function getVendorId(): int;

    /**
     * Get the idempotency key for this job execution, or null if not idempotent.
     */
    public function getIdempotencyKey(): ?string;
}
