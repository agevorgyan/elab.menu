<?php

namespace App\Models;

use App\Models\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use BelongsToVendor, HasFactory, SoftDeletes;

    protected $fillable = [
        'vendor_id',
        'location_id',
        'customer_id',
        'order_number',
        'table_number',
        'delivery_address',
        'type',
        'payment_method',
        'payment_status',
        'payment_transaction_id',
        'subtotal',
        'service_fee',
        'delivery_fee',
        'birthday_discount_amount',
        'total_amount',
        'status',
        'customer_name',
        'customer_phone',
        'customer_email',
        'customer_birthdate',
        'marketing_opt_in',
        'notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'service_fee' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'birthday_discount_amount' => 'decimal:2',
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
