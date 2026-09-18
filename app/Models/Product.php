<?php

namespace App\Models;

use App\Models\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory, BelongsToVendor, SoftDeletes;

    protected static function booted(): void
    {
        static::deleting(function (Product $product) {
            $product->deleteImageFile();
        });
    }

    /**
     * Delete the product's associated image file from public storage if stored locally.
     */
    public function deleteImageFile(): void
    {
        if (!empty($this->image) && !str_starts_with($this->image, 'http://') && !str_starts_with($this->image, 'https://')) {
            $path = ltrim(str_replace('/storage/', '', $this->image), '/');
            if (!empty($path) && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }
    }

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
        return $this->belongsTo(Category::class)->withTrashed();
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

    public function getTranslatedName(?string $lang = null): string
    {
        $lang = $lang ?: app()->getLocale();
        if ($this->name_translations && isset($this->name_translations[$lang]) && !empty($this->name_translations[$lang])) {
            return $this->name_translations[$lang];
        }
        return $this->name;
    }

    public function getTranslatedDescription(?string $lang = null): string
    {
        $lang = $lang ?: app()->getLocale();
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
