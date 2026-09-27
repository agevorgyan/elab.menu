<?php

namespace App\Models;

use App\Models\Traits\BelongsToVendor;
use App\Services\AuditSanitizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityAuditLog extends Model
{
    use BelongsToVendor, HasFactory;

    public $timestamps = false;

    protected $table = 'security_audit_logs';

    // Standardized event constants
    public const EVENT_LOGIN = 'login';

    public const EVENT_LOGOUT = 'logout';

    public const EVENT_FAILED_LOGIN = 'failed_login';

    public const EVENT_2FA_CHANGES = '2fa_changes';

    public const EVENT_PASSWORD_CHANGES = 'password_changes';

    public const EVENT_CREDENTIAL_CHANGES = 'credential_changes';

    public const EVENT_ROLE_CHANGES = 'role_changes';

    public const EVENT_VENDOR_SUSPENDED = 'vendor_suspended';

    public const EVENT_VENDOR_DELETED = 'vendor_deleted';

    public const EVENT_PAYMENT_STATUS_CHANGED = 'payment_status_changed';

    public const EVENT_SUBSCRIPTION_CHANGED = 'subscription_changed';

    public const EVENT_SECURITY_CONFIG_CHANGED = 'security_config_changed';

    protected $fillable = [
        'vendor_id',
        'user_id',
        'actor_type',
        'actor_id',
        'actor_name',
        'actor_email',
        'event',
        'action',
        'target_type',
        'target_id',
        'ip_address',
        'user_agent',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Defense-in-depth: Automatically scrub sensitive secrets from metadata before saving.
     */
    public function setMetadataAttribute(mixed $value): void
    {
        if (is_array($value)) {
            $value = app(AuditSanitizer::class)->sanitize($value);
            $this->attributes['metadata'] = json_encode($value);
        } elseif (is_string($value)) {
            $this->attributes['metadata'] = json_encode(app(AuditSanitizer::class)->sanitizeString($value));
        } else {
            $this->attributes['metadata'] = $value ? json_encode($value) : null;
        }
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class)->withoutGlobalScopes();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withoutGlobalScopes();
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id')->withoutGlobalScopes();
    }

    /**
     * Scope for filtering by event type.
     */
    public function scopeForEvent(Builder $query, string $event): Builder
    {
        return $query->where('event', $event);
    }
}
