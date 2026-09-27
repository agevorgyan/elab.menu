<?php

namespace App\Services;

use App\Models\Vendor;
use Closure;
use DateInterval;
use DateTimeInterface;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class TenantCache
{
    /**
     * Resolve vendor UUID string from Vendor model, vendor ID, or UUID string.
     */
    public static function resolveUuid(Vendor|string|int $vendor): string
    {
        if ($vendor instanceof Vendor) {
            if (empty($vendor->uuid)) {
                $vendor->uuid = (string) Str::uuid();
                $vendor->saveQuietly();
            }

            return (string) $vendor->uuid;
        }

        if (is_int($vendor) || ctype_digit((string) $vendor)) {
            $uuid = Vendor::withoutGlobalScopes()->where('id', (int) $vendor)->value('uuid');
            if ($uuid) {
                return (string) $uuid;
            }
        }

        if (is_string($vendor) && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $vendor)) {
            return strtolower($vendor);
        }

        if (is_string($vendor)) {
            $uuid = Vendor::withoutGlobalScopes()->where('slug', $vendor)->value('uuid');
            if ($uuid) {
                return (string) $uuid;
            }
        }

        return (string) $vendor;
    }

    /**
     * Generate standard tenant-sensitive cache key with vendor UUID.
     * Format: vendor:{uuid}:{suffix}
     *
     * Examples:
     * - vendor:{uuid}:menu
     * - vendor:{uuid}:settings
     * - vendor:{uuid}:analytics
     */
    public static function key(Vendor|string|int $vendor, string $suffix): string
    {
        $uuid = static::resolveUuid($vendor);
        $cleanSuffix = ltrim($suffix, ':');

        return "vendor:{$uuid}:{$cleanSuffix}";
    }

    /**
     * Retrieve an item from the tenant cache.
     */
    public static function get(Vendor|string|int $vendor, string $suffix, mixed $default = null): mixed
    {
        return Cache::get(static::key($vendor, $suffix), $default);
    }

    /**
     * Store an item in the tenant cache.
     */
    public static function put(Vendor|string|int $vendor, string $suffix, mixed $value, DateTimeInterface|DateInterval|int|null $ttl = null): bool
    {
        return Cache::put(static::key($vendor, $suffix), $value, $ttl);
    }

    /**
     * Get an item from the cache, or execute the given Closure and store the result.
     */
    public static function remember(Vendor|string|int $vendor, string $suffix, DateTimeInterface|DateInterval|int|null $ttl, Closure $callback): mixed
    {
        return Cache::remember(static::key($vendor, $suffix), $ttl, $callback);
    }

    /**
     * Remove an item from the tenant cache.
     */
    public static function forget(Vendor|string|int $vendor, string $suffix): bool
    {
        return Cache::forget(static::key($vendor, $suffix));
    }

    /**
     * Determine if an item exists in the tenant cache.
     */
    public static function has(Vendor|string|int $vendor, string $suffix): bool
    {
        return Cache::has(static::key($vendor, $suffix));
    }

    /**
     * Increment the value of an item in the tenant cache.
     */
    public static function increment(Vendor|string|int $vendor, string $suffix, int $value = 1): int|bool
    {
        return Cache::increment(static::key($vendor, $suffix), $value);
    }

    /**
     * Decrement the value of an item in the tenant cache.
     */
    public static function decrement(Vendor|string|int $vendor, string $suffix, int $value = 1): int|bool
    {
        return Cache::decrement(static::key($vendor, $suffix), $value);
    }

    /**
     * Standard tenant menu cache key.
     */
    public static function menuKey(Vendor|string|int $vendor): string
    {
        return static::key($vendor, 'menu');
    }

    /**
     * Standard tenant settings cache key.
     */
    public static function settingsKey(Vendor|string|int $vendor): string
    {
        return static::key($vendor, 'settings');
    }

    /**
     * Standard tenant analytics cache key.
     */
    public static function analyticsKey(Vendor|string|int $vendor): string
    {
        return static::key($vendor, 'analytics');
    }

    /**
     * Standard tenant job idempotency key.
     */
    public static function idempotencyKey(Vendor|string|int $vendor, string $jobKey): string
    {
        return static::key($vendor, "job_idempotency:{$jobKey}");
    }

    /**
     * Acquire a tenant-scoped cache lock.
     */
    public static function lock(Vendor|string|int $vendor, string $name, int $seconds = 0, ?string $owner = null): Lock
    {
        return Cache::lock(static::key($vendor, "lock:{$name}"), $seconds, $owner);
    }

    /**
     * Invalidate tenant menu cache, bumping the menu version for storefront queries.
     */
    public static function invalidateMenu(Vendor|string|int $vendor): void
    {
        static::forget($vendor, 'menu');
        $current = (int) static::get($vendor, 'menu_version', 1);
        static::put($vendor, 'menu_version', $current + 1, now()->addYear());
    }

    /**
     * Invalidate all primary cached data for a vendor.
     */
    public static function invalidateAll(Vendor|string|int $vendor): void
    {
        static::forget($vendor, 'menu');
        static::forget($vendor, 'menu_version');
        static::forget($vendor, 'settings');
        static::forget($vendor, 'analytics');
        static::forget($vendor, 'config');
        static::forget($vendor, 'details');
        static::forget($vendor, 'ai_quotas');
    }
}
