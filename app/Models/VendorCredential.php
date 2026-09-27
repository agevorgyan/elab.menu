<?php

namespace App\Models;

use App\Models\Traits\BelongsToVendor;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model representing an encrypted, tenant-isolated vendor credential.
 *
 * @property int $id
 * @property int $vendor_id
 * @property string $provider
 * @property string $credential_type
 * @property string $encrypted_value
 * @property array|null $metadata
 * @property Carbon|null $last_verified_at
 * @property Carbon|null $rotated_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class VendorCredential extends Model
{
    use BelongsToVendor, HasFactory;

    protected $fillable = [
        'vendor_id',
        'provider',
        'credential_type',
        'encrypted_value',
        'metadata',
        'last_verified_at',
        'rotated_at',
    ];

    /**
     * Hidden from JSON and array serialization.
     */
    protected $hidden = [
        'encrypted_value',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'encrypted_value' => 'encrypted',
            'metadata' => 'array',
            'last_verified_at' => 'datetime',
            'rotated_at' => 'datetime',
        ];
    }

    /**
     * Protect sensitive values from being revealed in debuggers, dumps, and logs.
     *
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        $attributes = $this->getAttributes();
        $attributes['encrypted_value'] = '[REDACTED]';

        return $attributes;
    }

    /**
     * Generate a safe masked string for display in UI or logs.
     */
    public function maskedValue(): string
    {
        $raw = $this->encrypted_value;
        if (empty($raw)) {
            return '';
        }

        $length = strlen($raw);
        if ($length <= 8) {
            return '••••••••';
        }

        // Show prefix if present (e.g. sk_test_, sk_live_) or first 4 chars
        $prefix = substr($raw, 0, 4);
        $suffix = substr($raw, -4);

        return $prefix.'••••••••'.$suffix;
    }

    /**
     * Format a safe representation for frontend consumption.
     *
     * @return array{configured: bool, provider: string, credential_type: string, masked: string, last_verified_at: ?string, rotated_at: ?string}
     */
    public function toPublicSummary(): array
    {
        return [
            'configured' => true,
            'provider' => $this->provider,
            'credential_type' => $this->credential_type,
            'masked' => $this->maskedValue(),
            'last_verified_at' => $this->last_verified_at?->toISOString(),
            'rotated_at' => $this->rotated_at?->toISOString(),
        ];
    }
}
