<?php

namespace App\Models;

use App\Models\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaiterCall extends Model
{
    use BelongsToVendor, HasFactory;

    protected $fillable = [
        'vendor_id',
        'location_id',
        'table_number',
        'type',
        'status',
        'notes',
    ];

    /**
     * Relationship to the vendor.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Relationship to the location/branch.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Scope to filter pending calls.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Get a human-readable label for the call type.
     */
    public function getTypeLabel(): string
    {
        return match ($this->type) {
            'call_waiter' => 'Մատուցողի կանչ',
            'bill_cash' => 'Հաշիվ (Կանխիկ)',
            'bill_card' => 'Հաշիվ (Քարտով)',
            default => 'Կանչ',
        };
    }

    /**
     * Get FontAwesome icon class for the call type.
     */
    public function getTypeIcon(): string
    {
        return match ($this->type) {
            'call_waiter' => 'fa-solid fa-bell',
            'bill_cash' => 'fa-solid fa-money-bill-wave',
            'bill_card' => 'fa-solid fa-credit-card',
            default => 'fa-solid fa-bell-concierge',
        };
    }

    /**
     * Get badge color styling for the type.
     */
    public function getTypeBadgeClass(): string
    {
        return match ($this->type) {
            'call_waiter' => 'bg-amber-500/20 text-amber-500 border-amber-500/30',
            'bill_cash' => 'bg-emerald-500/20 text-emerald-500 border-emerald-500/30',
            'bill_card' => 'bg-blue-500/20 text-blue-500 border-blue-500/30',
            default => 'bg-slate-500/20 text-slate-400 border-slate-500/30',
        };
    }
}
