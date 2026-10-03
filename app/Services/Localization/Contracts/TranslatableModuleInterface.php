<?php

namespace App\Services\Localization\Contracts;

interface TranslatableModuleInterface
{
    /**
     * Unique module namespace identifier (e.g. 'menu', 'orders', 'common').
     */
    public function getNamespace(): string;

    /**
     * Human-readable module name.
     */
    public function getName(): string;

    /**
     * Required locales that must have complete translation files.
     *
     * @return array<int, string>
     */
    public function getRequiredLocales(): array;

    /**
     * Translatable Eloquent model classes belonging to this module.
     *
     * @return array<int, class-string>
     */
    public function getTranslatableModels(): array;

    /**
     * List of mandatory UI translation keys for this module.
     *
     * @return array<int, string>
     */
    public function getExpectedKeys(): array;

    /**
     * Validate completeness and placeholder integrity for this module.
     *
     * @return array{is_valid: bool, missing_files: array, missing_keys: array, placeholder_errors: array}
     */
    public function audit(): array;
}
