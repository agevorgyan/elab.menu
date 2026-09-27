<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tenant Data Retention Period (Days)
    |--------------------------------------------------------------------------
    |
    | When a vendor requests termination, tenant operational data is held in
    | retention for this duration before permanent deletion can be queued.
    |
    */
    'retention_days' => env('TENANT_RETENTION_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Default Plan Storage Quotas (Bytes)
    |--------------------------------------------------------------------------
    */
    'storage_quotas' => [
        'basic' => 104857600,        // 100 MB
        'pro' => 524288000,          // 500 MB
        'business' => 2147483648,    // 2 GB
        'custom' => 10737418240,     // 10 GB
    ],
];
