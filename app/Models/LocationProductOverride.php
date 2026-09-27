<?php

namespace App\Models;

use App\Models\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class LocationProductOverride extends Model
{
    use BelongsToVendor, HasFactory;

    protected $fillable = [
        'vendor_id',
        'location_id',
        'product_id',
        'override_price',
        'is_available',
    ];

    protected $casts = [
        'override_price' => 'decimal:2',
        'is_available' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (LocationProductOverride $override): void {
            if ($override->override_price !== null && $override->override_price < 0) {
                throw new InvalidArgumentException('Override price cannot be negative.');
            }

            $product = $override->product ?? Product::withoutGlobalScopes()->find($override->product_id);
            $location = $override->location ?? Location::withoutGlobalScopes()->find($override->location_id);

            if ($product && empty($override->vendor_id)) {
                $override->vendor_id = $product->vendor_id;
            }

            if ($location && empty($override->vendor_id)) {
                $override->vendor_id = $location->vendor_id;
            }

            if ($product && $location) {
                if (
                    (int) $product->vendor_id !== (int) $location->vendor_id ||
                    (int) $override->vendor_id !== (int) $product->vendor_id
                ) {
                    throw new InvalidArgumentException(
                        "Tenant isolation violation: LocationProductOverride vendor_id ({$override->vendor_id}), location vendor_id ({$location->vendor_id}), and product vendor_id ({$product->vendor_id}) must match."
                    );
                }
            }
        });
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
