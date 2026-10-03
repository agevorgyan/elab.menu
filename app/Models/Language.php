<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Language extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'native_name',
        'flag',
        'direction',
        'is_active',
        'is_default',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Scope query to only active languages.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order', 'asc');
    }

    /**
     * Scope query to the system default language.
     */
    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    /**
     * Relationship to vendor languages pivot records.
     */
    public function vendorLanguages(): HasMany
    {
        return $this->hasMany(VendorLanguage::class);
    }

    /**
     * Relationship to vendors that have enabled this language.
     */
    public function vendors(): BelongsToMany
    {
        return $this->belongsToMany(Vendor::class, 'vendor_languages')
            ->withPivot(['is_default', 'is_active', 'sort_order'])
            ->withTimestamps();
    }

    /**
     * Check if language text direction is right-to-left.
     */
    public function isRtl(): bool
    {
        return strtolower($this->direction) === 'rtl';
    }

    /**
     * Get display string with flag and native name.
     */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->flag} {$this->name} ({$this->native_name})";
    }
}
