<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocationProductOverride extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_id',
        'product_id',
        'override_price',
        'is_available',
    ];

    protected $casts = [
        'override_price' => 'decimal:2',
        'is_available' => 'boolean',
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
