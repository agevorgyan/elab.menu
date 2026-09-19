<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariation extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',
        'name_translations',
        'price',
        'is_default',
    ];

    protected $casts = [
        'name_translations' => 'array',
        'price' => 'decimal:2',
        'is_default' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the translated name of the variation based on the current or specified locale.
     */
    public function getTranslatedName(?string $lang = null): string
    {
        $lang = $lang ?: app()->getLocale();
        if ($this->name_translations && isset($this->name_translations[$lang]) && ! empty($this->name_translations[$lang])) {
            return $this->name_translations[$lang];
        }

        return $this->name;
    }
}
