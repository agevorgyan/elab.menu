<?php

namespace App\Models;

use App\Models\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use BelongsToVendor, HasFactory;

    protected $fillable = [
        'vendor_id',
        'location_id',
        'name',
        'phone',
        'email',
        'birthdate',
        'address',
        'notes',
        'marketing_opt_in',
        'total_orders_count',
        'total_spent',
        'last_order_at',
    ];

    protected $casts = [
        'marketing_opt_in' => 'boolean',
        'total_spent' => 'decimal:2',
        'last_order_at' => 'datetime',
        'birthdate' => 'date',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class)->latest();
    }

    public function recalculateStats()
    {
        $this->total_orders_count = $this->orders()->count();
        $this->total_spent = $this->orders()->sum('total_amount');
        $this->last_order_at = $this->orders()->max('created_at');
        $this->save();
    }
}
