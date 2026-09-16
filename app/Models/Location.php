<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'name',
        'slug',
        'address',
        'phone',
        'whatsapp_number',
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
