<?php

namespace App\Services\Localization\Modules;

use App\Models\Category;
use App\Models\Product;

class MenuModule extends AbstractTranslatableModule
{
    public function getNamespace(): string
    {
        return 'menu';
    }

    public function getName(): string
    {
        return 'Digital Menu & Storefront';
    }

    public function getTranslatableModels(): array
    {
        return [
            Product::class,
            Category::class,
        ];
    }

    public function getExpectedKeys(): array
    {
        return [
            'price',
            'add',
            'mins',
            'allergens',
            'quantity',
            'cart',
            'confirm_order',
        ];
    }
}
