<?php

namespace Tests\Unit;

use App\Models\Language;
use App\Models\Vendor;
use App\Services\Localization\LocaleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LocaleManagerTest extends TestCase
{
    use RefreshDatabase;

    protected LocaleManager $localeManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->localeManager = app(LocaleManager::class);

        Language::firstOrCreate(
            ['code' => 'en'],
            ['name' => 'English', 'native_name' => 'English', 'flag' => '🇬🇧', 'direction' => 'ltr', 'is_active' => true, 'is_default' => true, 'sort_order' => 1]
        );
        Language::firstOrCreate(
            ['code' => 'hy'],
            ['name' => 'Armenian', 'native_name' => 'Հայերեն', 'flag' => '🇦🇲', 'direction' => 'ltr', 'is_active' => true, 'is_default' => false, 'sort_order' => 2]
        );
        Language::firstOrCreate(
            ['code' => 'ru'],
            ['name' => 'Russian', 'native_name' => 'Русский', 'flag' => '🇷🇺', 'direction' => 'ltr', 'is_active' => true, 'is_default' => false, 'sort_order' => 3]
        );
        $this->localeManager->clearCache();
    }

    public function test_active_languages_and_locales(): void
    {
        $locales = $this->localeManager->getActiveLocales();

        $this->assertContains('en', $locales);
        $this->assertContains('hy', $locales);
        $this->assertContains('ru', $locales);
        $this->assertTrue($this->localeManager->isValidLocale('en'));
        $this->assertTrue($this->localeManager->isValidLocale('hy'));
        $this->assertFalse($this->localeManager->isValidLocale('xyz_unsupported'));
    }

    public function test_system_default_and_fallback(): void
    {
        $default = $this->localeManager->getSystemDefaultLanguage();
        $this->assertEquals('en', $default->code);
        $this->assertEquals('en', $this->localeManager->getSystemDefaultLocale());
        $this->assertEquals('en', $this->localeManager->getFallbackLocale());
    }

    public function test_resolve_storefront_locale(): void
    {
        $vendor = Vendor::create([
            'name' => 'Test Bistro',
            'slug' => 'test-bistro-i18n',
            'supported_languages' => [
                ['code' => 'en', 'name' => 'English', 'is_default' => true],
                ['code' => 'hy', 'name' => 'Armenian', 'is_default' => false],
            ],
        ]);

        // 1. With explicit query param ?lang=hy
        $requestWithHy = Request::create('/m/test-bistro-i18n?lang=hy', 'GET');
        $resolved = $this->localeManager->resolveStorefrontLocale($requestWithHy, $vendor);
        $this->assertEquals('hy', $resolved);

        // 2. With unsupported query param ?lang=de (vendor only has en and hy)
        $requestWithDe = Request::create('/m/test-bistro-i18n?lang=de', 'GET');
        $resolvedFallback = $this->localeManager->resolveStorefrontLocale($requestWithDe, $vendor);
        $this->assertEquals('en', $resolvedFallback);
    }

    public function test_currency_formatting(): void
    {
        $amd = $this->localeManager->formatCurrency(15000, 'AMD');
        $this->assertStringContainsString('15,000', $amd);
        $this->assertStringContainsString('֏', $amd);

        $usd = $this->localeManager->formatCurrency(25.50, 'USD');
        $this->assertStringContainsString('$', $usd);
    }

    public function test_date_formatting(): void
    {
        $dateStr = '2026-10-03';
        $formattedEn = $this->localeManager->formatDate($dateStr, null, 'en');
        $this->assertNotEmpty($formattedEn);
    }

    public function test_get_active_languages_self_heals_when_cache_is_corrupted_or_contains_incomplete_class(): void
    {
        // 1. Simulate corrupted non-collection/corrupt object payload in cache
        Cache::put(LocaleManager::CACHE_KEY_LANGUAGES, 'corrupted_serialized_payload', 3600);

        $languages = $this->localeManager->getActiveLanguages();

        $this->assertInstanceOf(Collection::class, $languages);
        $this->assertNotEmpty($languages);
        $this->assertInstanceOf(Language::class, $languages->first());
        $this->assertEquals('en', $languages->first()->code);
    }
}
