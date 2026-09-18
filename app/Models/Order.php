<?php

namespace App\Models;

use App\Models\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory, BelongsToVendor;

    protected $fillable = [
        'vendor_id',
        'location_id',
        'customer_id',
        'order_number',
        'table_number',
        'type',
        'total_amount',
        'status',
        'customer_name',
        'customer_phone',
        'customer_email',
        'marketing_opt_in',
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'marketing_opt_in' => 'boolean',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
