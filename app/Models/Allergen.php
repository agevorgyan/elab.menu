<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Allergen extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'name_translations',
        'icon',
    ];

    protected $casts = [
        'name_translations' => 'array',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_allergens');
    }
}
