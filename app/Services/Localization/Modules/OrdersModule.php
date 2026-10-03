<?php

namespace App\Services\Localization\Modules;

class OrdersModule extends AbstractTranslatableModule
{
    public function getNamespace(): string
    {
        return 'orders';
    }

    public function getName(): string
    {
        return 'Orders & Kitchen Operations';
    }

    public function getExpectedKeys(): array
    {
        return [
            'order_placed',
            'order_status',
            'items',
            'total',
            'dine_in',
            'takeaway',
            'delivery',
            'order_number',
            'table',
        ];
    }
}
