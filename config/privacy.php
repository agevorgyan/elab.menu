<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Data Retention Policies (Days)
    |--------------------------------------------------------------------------
    |
    | Defines the authoritative retention windows for sensitive personal data,
    | access logs, analytics, and AI conversation history across the platform.
    |
    */
    'retention_periods' => [
        // Security and compliance audit logs
        'security_audit_logs_days' => 365,

        // Page visits, visitor IP addresses, and user-agent analytics
        'analytics_logs_days' => 90,

        // AI Waiter conversation sessions, history, and usage logs
        'ai_conversations_days' => 60,

        // Raw IP addresses in logs anonymized or hashed after this window
        'ip_anonymization_days' => 30,

        // Inactive CRM customer profiles without order activity
        'inactive_customers_days' => 730,

        // Retention window before permanent deletion of terminated vendor data
        'terminated_vendor_days' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Sensitive Keys Subject to Immediate Redaction
    |--------------------------------------------------------------------------
    |
    | Any metadata key matching these patterns (case-insensitive substring or
    | exact match) will be stripped or replaced with [REDACTED] before logging.
    |
    */
    'sensitive_keys' => [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'old_password',
        'secret',
        'secret_key',
        'api_key',
        'bot_token',
        'token',
        'access_token',
        'refresh_token',
        'auth_token',
        'authorization',
        'bearer',
        'two_factor_secret',
        'two_factor_code',
        'two_factor_recovery_codes',
        'cvv',
        'cvc',
        'card_number',
        'card_secret',
        'pan',
        'session_id',
        'remember_token',
        'wifi_password',
        'private_key',
    ],

    /*
    |--------------------------------------------------------------------------
    | Regex Patterns for Embedded Secrets in Strings
    |--------------------------------------------------------------------------
    */
    'string_patterns' => [
        // Telegram bot tokens: bot123456:ABC...
        '/bot(\d{5,}:[A-Za-z0-9_-]{20,})/i' => 'bot[REDACTED_TELEGRAM_TOKEN]',

        // Stripe API secret keys: sk_live_..., sk_test_..., rk_live_...
        '/(sk|rk)_(live|test)_[0-9a-zA-Z]{16,}/' => '$1_$2_[REDACTED_STRIPE_KEY]',

        // Bearer tokens in headers or strings: Bearer eyJ...
        '/Bearer\s+[A-Za-z0-9\-\._~\+\/]+=*/i' => 'Bearer [REDACTED_BEARER_TOKEN]',

        // URL query parameters with keys or secrets
        '/([?&](?:key|api_key|secret|secret_key|token|bot_token|password)=)[^&\s]+/i' => '$1[REDACTED]',
    ],
];
