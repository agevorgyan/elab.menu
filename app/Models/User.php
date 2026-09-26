<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'vendor_id',
        'location_id',
        'phone',
        'two_factor_enabled',
        'two_factor_type',
        'two_factor_secret',
        'two_factor_confirmed_at',
        'two_factor_email_code',
        'two_factor_email_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_email_code',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_enabled' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_email_expires_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (User $user) {
            if ($user->location_id && $user->vendor_id) {
                $location = Location::withoutGlobalScopes()->find($user->location_id);
                if ($location && (int) $location->vendor_id !== (int) $user->vendor_id) {
                    throw new \InvalidArgumentException('Cross-vendor location assigned to user.');
                }
            }
        });
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isVendorOwner(): bool
    {
        return $this->role === 'vendor_owner';
    }

    public function isManager(): bool
    {
        return in_array($this->role, ['vendor_owner', 'manager']);
    }

    public function hasTwoFactorEnabled(): bool
    {
        return (bool) $this->two_factor_enabled;
    }

    public function maskedEmail(): string
    {
        $parts = explode('@', $this->email);
        $name = $parts[0] ?? '';
        $domain = $parts[1] ?? '';

        if (strlen($name) <= 2) {
            $maskedName = substr($name, 0, 1).'*';
        } else {
            $maskedName = substr($name, 0, 2).str_repeat('*', max(strlen($name) - 3, 2)).substr($name, -1);
        }

        return $maskedName.'@'.$domain;
    }
}
