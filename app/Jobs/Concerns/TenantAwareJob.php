<?php

namespace App\Jobs\Concerns;

use App\Jobs\Middleware\TenantJobMiddleware;
use Illuminate\Support\Facades\Log;
use ReflectionClass;
use ReflectionProperty;

trait TenantAwareJob
{
    /**
     * Mandatory tenant vendor ID.
     */
    public int $vendorId;

    /**
     * Optional idempotency key to prevent duplicate execution.
     */
    public ?string $idempotencyKey = null;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 30, 90];
    }

    /**
     * Get the tenant vendor ID.
     */
    public function getVendorId(): int
    {
        return (int) ($this->vendorId ?? 0);
    }

    /**
     * Set the tenant vendor ID.
     */
    public function setVendorId(int $vendorId): static
    {
        $this->vendorId = $vendorId;

        return $this;
    }

    /**
     * Get the idempotency key.
     */
    public function getIdempotencyKey(): ?string
    {
        return $this->idempotencyKey;
    }

    /**
     * Set the idempotency key.
     */
    public function setIdempotencyKey(?string $key): static
    {
        $this->idempotencyKey = $key;

        return $this;
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            new TenantJobMiddleware,
        ];
    }

    /**
     * Handle job failure and ensure sensitive payloads are redacted from logs.
     */
    public function failed(\Throwable $exception): void
    {
        $sanitizedPayload = $this->getSanitizedPayload();

        Log::error(sprintf(
            'Tenant job [%s] failed for vendor #%s: %s',
            class_basename(static::class),
            $this->vendorId ?? 'unknown',
            $exception->getMessage()
        ), [
            'job' => static::class,
            'vendor_id' => $this->vendorId ?? null,
            'idempotency_key' => $this->idempotencyKey ?? null,
            'sanitized_payload' => $sanitizedPayload,
        ]);
    }

    /**
     * Redact sensitive fields from the job payload before logging or serialization.
     */
    public function getSanitizedPayload(): array
    {
        $sensitiveKeyPatterns = [
            'password', 'password_confirmation', 'secret', 'token', 'access_token',
            'refresh_token', 'api_key', 'apikey', 'key', 'auth', 'authorization',
            'bot_token', 'telegram_token', 'webhook_secret', 'card', 'cvv',
            'cvc', 'stripe_secret', 'private_key', 'credentials', 'phone',
            'pin', 'two_factor_secret', 'two_factor_recovery_codes',
        ];

        $reflect = new ReflectionClass($this);
        $properties = $reflect->getProperties(ReflectionProperty::IS_PUBLIC);
        $payload = [];

        foreach ($properties as $property) {
            $name = $property->getName();
            if ($property->isInitialized($this)) {
                $value = $property->getValue($this);
                $payload[$name] = $this->sanitizeValue($name, $value, $sensitiveKeyPatterns);
            }
        }

        return $payload;
    }

    /**
     * Recursively sanitize values to prevent sensitive data leakage.
     */
    protected function sanitizeValue(string $key, mixed $value, array $sensitivePatterns): mixed
    {
        $lowerKey = strtolower($key);
        foreach ($sensitivePatterns as $pattern) {
            if (str_contains($lowerKey, $pattern)) {
                return '[REDACTED]';
            }
        }

        if (is_array($value)) {
            $sanitized = [];
            foreach ($value as $k => $v) {
                $sanitized[$k] = $this->sanitizeValue((string) $k, $v, $sensitivePatterns);
            }

            return $sanitized;
        }

        if (is_object($value)) {
            if (method_exists($value, 'toArray')) {
                return $this->sanitizeValue($key, $value->toArray(), $sensitivePatterns);
            }

            return get_class($value).'#'.($value->id ?? spl_object_id($value));
        }

        return $value;
    }
}
