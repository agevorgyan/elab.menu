<?php

namespace App\Services\Localization\Contracts;

use App\Models\Vendor;
use App\Services\Localization\DTOs\TranslationResult;

interface TranslationProviderInterface
{
    /**
     * Translate a single text string from source locale to target locale.
     *
     * @param  array<string, mixed>  $context
     */
    public function translate(string $text, string $sourceLocale, string $targetLocale, array $context = []): TranslationResult;

    /**
     * Translate a batch of texts.
     *
     * @param  array<string, string>  $texts  [key => text]
     * @param  array<string, mixed>  $context
     * @return array<string, TranslationResult>
     */
    public function translateBatch(array $texts, string $sourceLocale, string $targetLocale, array $context = []): array;

    /**
     * Provider identifier (e.g. 'ai_gateway', 'mock').
     */
    public function getName(): string;

    /**
     * Whether provider is configured and available.
     */
    public function isAvailable(?Vendor $vendor = null): bool;
}
