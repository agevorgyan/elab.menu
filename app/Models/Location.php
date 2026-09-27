<?php

namespace App\Models;

use App\Models\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Location extends Model
{
    use BelongsToVendor, HasFactory;

    protected $fillable = [
        'vendor_id',
        'name',
        'slug',
        'address',
        'phone',
        'whatsapp_number',
        'telegram_chat_id',
        'wifi_ssid',
        'wifi_password',
        'working_hours',
        'opening_hours',
        'table_count',
        'allow_dine_in_orders',
        'allow_whatsapp_orders',
        'minimum_order_amount',
        'is_active',
    ];

    protected $casts = [
        'opening_hours' => 'array',
        'allow_dine_in_orders' => 'boolean',
        'allow_whatsapp_orders' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Transparently decrypt Wi-Fi password when retrieved, falling back to plaintext for legacy rows.
     */
    public function getWifiPasswordAttribute($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return $value;
        }
    }

    /**
     * Transparently encrypt Wi-Fi password when stored at rest in the database.
     */
    public function setWifiPasswordAttribute(?string $value): void
    {
        $this->attributes['wifi_password'] = ($value !== null && $value !== '')
            ? Crypt::encryptString($value)
            : null;
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function productOverrides()
    {
        return $this->hasMany(LocationProductOverride::class);
    }
}
