<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

class CustomDomain extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_VERIFYING = 'verifying';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_FAILED = 'failed';

    public const METHOD_DNS_TXT = 'dns_txt';

    public const METHOD_DNS_CNAME = 'dns_cname';

    public const METHOD_HTTP_FILE = 'http_file';

    public const DNS_PENDING = 'pending';

    public const DNS_DETECTED = 'detected';

    public const DNS_FAILED = 'failed';

    public const SSL_PENDING = 'pending';

    public const SSL_ACTIVE = 'active';

    public const SSL_FAILED = 'failed';

    public const SSL_EXPIRED = 'expired';

    protected $fillable = [
        'vendor_id',
        'domain',
        'normalized_domain',
        'is_primary',
        'verification_token',
        'verification_method',
        'verified_at',
        'dns_status',
        'dns_detected_at',
        'ssl_status',
        'ssl_expires_at',
        'status',
        'failure_reason',
        'last_checked_at',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'verified_at' => 'datetime',
        'dns_detected_at' => 'datetime',
        'ssl_expires_at' => 'datetime',
        'last_checked_at' => 'datetime',
    ];

    /**
     * Boot model events for automatic token generation and cache management.
     */
    protected static function booted(): void
    {
        static::creating(function (CustomDomain $model) {
            if (empty($model->normalized_domain) && ! empty($model->domain)) {
                $model->normalized_domain = static::normalize($model->domain);
            }

            if (empty($model->verification_token)) {
                $model->verification_token = static::generateVerificationToken();
            }

            if (empty($model->status)) {
                $model->status = self::STATUS_PENDING;
            }
        });

        static::saved(function (CustomDomain $model) {
            // Keep vendor.custom_domain in sync with the primary active domain
            if ($model->is_primary && $model->status === self::STATUS_ACTIVE) {
                $vendor = $model->vendor;
                if ($vendor && $vendor->custom_domain !== $model->normalized_domain) {
                    $vendor->custom_domain = $model->normalized_domain;
                    $vendor->saveQuietly();
                }
            }

            // Invalidate domain cache
            Cache::forget('domain_'.$model->normalized_domain);
        });

        static::deleted(function (CustomDomain $model) {
            Cache::forget('domain_'.$model->normalized_domain);

            // If the deleted domain was the vendor's primary custom_domain, clear or reassign
            $vendor = $model->vendor;
            if ($vendor && $vendor->custom_domain === $model->normalized_domain) {
                $nextPrimary = $vendor->customDomains()
                    ->where('id', '!=', $model->id)
                    ->where('status', self::STATUS_ACTIVE)
                    ->first();

                $vendor->custom_domain = $nextPrimary?->normalized_domain;
                $vendor->saveQuietly();
            }
        });
    }

    /**
     * Normalize domains consistently.
     * Strips scheme, ports, path fragments, trailing slashes/dots and converts to lowercase.
     * Validates syntax and disallows reserved platform domains.
     */
    public static function normalize(string $domain): string
    {
        $domain = trim($domain);

        // Strip http:// or https://
        $clean = preg_replace('#^https?://#i', '', $domain);

        // Strip path, query params, fragments
        $clean = explode('/', $clean)[0];
        $clean = explode('?', $clean)[0];
        $clean = explode('#', $clean)[0];

        // Strip port numbers
        $clean = explode(':', $clean)[0];

        // Convert to lowercase
        $clean = strtolower(trim($clean));

        // Strip trailing dots
        $clean = rtrim($clean, '.');

        if (empty($clean)) {
            throw new InvalidArgumentException('Դոմենի հասցեն չի կարող դատարկ լինել։');
        }

        // Validate basic hostname format: must have at least one dot, valid chars, no consecutive dots
        if (! preg_match('/^(?!-)[a-z0-9-]{1,63}(?<!-)(\.[a-z0-9-]{1,63}(?<!-))+$/i', $clean)) {
            throw new InvalidArgumentException("Անվավեր դոմենային ձևաչափ: [{$domain}]: Դոմենը պետք է լինի վավեր հասցե (օրինակ՝ menu.restaurant.am կամ restaurant.com):");
        }

        if (strlen($clean) > 253) {
            throw new InvalidArgumentException('Դոմենի երկարությունը չի կարող գերազանցել 253 նիշը։');
        }

        // Check against platform reserved domains
        $reserved = [
            'localhost',
            '127.0.0.1',
            'qrmenu.local',
            'elab.am',
            'menu.elab.am',
            'elab.menu',
        ];

        $mainHost = strtolower(parse_url(config('app.url', 'https://menu.elab.am'), PHP_URL_HOST) ?? '');
        if (! empty($mainHost)) {
            $reserved[] = $mainHost;
        }

        if (in_array($clean, $reserved, true)) {
            throw new InvalidArgumentException('Հիմնական հարթակի դոմենը չի կարող օգտագործվել որպես սեփական դոմեն։');
        }

        return $clean;
    }

    /**
     * Generate high-entropy cryptographic verification token.
     */
    public static function generateVerificationToken(): string
    {
        return 'elab-verify-'.bin2hex(random_bytes(24));
    }

    /**
     * Get the DNS challenge TXT host record name.
     */
    public function getChallengeHost(): string
    {
        return '_elab-challenge.'.$this->normalized_domain;
    }

    /**
     * Relationship to the owning vendor.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class)->withoutGlobalScopes();
    }

    /**
     * Scope query to active domains.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope query to verified domains.
     */
    public function scopeVerified(Builder $query): Builder
    {
        return $query->whereNotNull('verified_at');
    }

    /**
     * Scope query to primary domain.
     */
    public function scopePrimary(Builder $query): Builder
    {
        return $query->where('is_primary', true);
    }

    /**
     * Determine if ownership is cryptographically verified.
     */
    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    /**
     * Determine if DNS resolution is pointed to our platform.
     */
    public function isDnsDetected(): bool
    {
        return $this->dns_status === self::DNS_DETECTED && $this->dns_detected_at !== null;
    }

    /**
     * Determine if domain is live and serving traffic.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Transition state to verifying.
     */
    public function markVerifying(): void
    {
        $this->update([
            'status' => self::STATUS_VERIFYING,
            'last_checked_at' => now(),
        ]);
    }

    /**
     * Mark domain ownership as verified.
     */
    public function markVerified(string $method = self::METHOD_DNS_TXT): void
    {
        $this->update([
            'verified_at' => now(),
            'verification_method' => $method,
            'status' => $this->isDnsDetected() ? self::STATUS_ACTIVE : self::STATUS_VERIFYING,
            'failure_reason' => null,
            'last_checked_at' => now(),
        ]);

        if ($this->is_primary && $this->status === self::STATUS_ACTIVE) {
            $this->vendor->update(['custom_domain' => $this->normalized_domain]);
        }
    }

    /**
     * Mark DNS routing as successfully detected.
     */
    public function markDnsDetected(): void
    {
        $this->update([
            'dns_status' => self::DNS_DETECTED,
            'dns_detected_at' => now(),
            'status' => $this->isVerified() ? self::STATUS_ACTIVE : self::STATUS_VERIFYING,
            'failure_reason' => null,
            'last_checked_at' => now(),
        ]);

        if ($this->is_primary && $this->status === self::STATUS_ACTIVE) {
            $this->vendor->update(['custom_domain' => $this->normalized_domain]);
        }
    }

    /**
     * Mark both verified and DNS detected, activating domain immediately.
     */
    public function markActive(): void
    {
        $this->update([
            'verified_at' => $this->verified_at ?? now(),
            'dns_status' => self::DNS_DETECTED,
            'dns_detected_at' => $this->dns_detected_at ?? now(),
            'status' => self::STATUS_ACTIVE,
            'failure_reason' => null,
            'last_checked_at' => now(),
        ]);

        if ($this->is_primary) {
            $this->vendor->update(['custom_domain' => $this->normalized_domain]);
        }
    }

    /**
     * Suspend domain routing.
     */
    public function markSuspended(?string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_SUSPENDED,
            'failure_reason' => $reason,
            'last_checked_at' => now(),
        ]);

        Cache::forget('domain_'.$this->normalized_domain);

        if ($this->vendor && $this->vendor->custom_domain === $this->normalized_domain) {
            $this->vendor->update(['custom_domain' => null]);
        }
    }

    /**
     * Mark domain verification or DNS failure.
     */
    public function markFailed(string $reason): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'failure_reason' => $reason,
            'last_checked_at' => now(),
        ]);
    }

    /**
     * Set this domain as primary for its vendor, demoting any existing primary.
     */
    public function makePrimary(): void
    {
        static::where('vendor_id', $this->vendor_id)
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);

        $this->update(['is_primary' => true]);

        if ($this->isActive()) {
            $this->vendor->update(['custom_domain' => $this->normalized_domain]);
        }
    }
}
