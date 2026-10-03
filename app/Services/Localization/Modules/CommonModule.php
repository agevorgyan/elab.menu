<?php

namespace App\Services\Localization\Modules;

class CommonModule extends AbstractTranslatableModule
{
    public function getNamespace(): string
    {
        return 'common';
    }

    public function getName(): string
    {
        return 'Common Shared UI';
    }

    public function getExpectedKeys(): array
    {
        return [
            'save',
            'cancel',
            'edit',
            'delete',
            'search',
            'loading',
            'success',
            'error',
        ];
    }
}
