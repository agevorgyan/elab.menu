<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'category_id',
        'name',
        'name_translations',
        'description',
        'description_translations',
        'price',
        'image',
        'gallery',
        'dietary_tags',
        'calories',
        'protein_g',
        'carbs_g',
        'fat_g',
        'preparation_time_min',
        'is_featured',
        'is_available',
        'sort_order',
    ];

    protected $casts = [
        'name_translations' => 'array',
        'description_translations' => 'array',
        'gallery' => 'array',
        'dietary_tags' => 'array',
        'price' => 'decimal:2',
        'protein_g' => 'decimal:1',
        'carbs_g' => 'decimal:1',
        'fat_g' => 'decimal:1',
        'is_featured' => 'boolean',
        'is_available' => 'boolean',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function variations()
    {
        return $this->hasMany(ProductVariation::class);
    }

    public function allergens()
    {
        return $this->belongsToMany(Allergen::class, 'product_allergens');
    }

    public function overrides()
    {
        return $this->hasMany(LocationProductOverride::class);
    }

    public function getTranslatedName(string $lang = 'hy'): string
    {
        if ($this->name_translations && isset($this->name_translations[$lang]) && !empty($this->name_translations[$lang])) {
            return $this->name_translations[$lang];
        }
        return $this->name;
    }

    public function getTranslatedDescription(string $lang = 'hy'): string
    {
        if ($this->description_translations && isset($this->description_translations[$lang]) && !empty($this->description_translations[$lang])) {
            return $this->description_translations[$lang];
        }
        return $this->description ?? '';
    }

    public function getEffectivePrice(?int $locationId = null): float
    {
        if ($locationId) {
            $override = $this->overrides->firstWhere('location_id', $locationId);
            if ($override && $override->override_price !== null) {
                return (float) $override->override_price;
            }
        }
        return (float) $this->price;
    }

    public function isAvailableAtLocation(?int $locationId = null): bool
    {
        if (!$this->is_available) return false;
        if ($locationId) {
            $override = $this->overrides->firstWhere('location_id', $locationId);
            if ($override !== null && isset($override->is_available)) {
                return (bool) $override->is_available;
            }
        }
        return true;
    }
}
