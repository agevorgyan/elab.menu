<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\ContentTranslation;
use App\Models\Product;
use App\Models\TranslationGlossary;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentTranslationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_and_publish_content_translation(): void
    {
        $vendor = Vendor::create([
            'name' => 'Bistro Hub',
            'slug' => 'bistro-hub',
        ]);

        $category = Category::create([
            'vendor_id' => $vendor->id,
            'name' => 'Appetizers',
        ]);

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Beef Tartare',
            'description' => 'Fresh raw beef with egg yolk and herbs.',
            'price' => 4500,
        ]);

        // 1. Create a draft translation
        $draft = $product->setTranslation(
            field: 'name',
            locale: 'hy',
            translation: 'Տավարի Տարտար',
            status: ContentTranslation::STATUS_DRAFT,
            sourceType: ContentTranslation::SOURCE_AI,
            aiProvider: 'gemini',
            aiModel: 'gemini-3.6-flash'
        );

        $this->assertEquals(ContentTranslation::STATUS_DRAFT, $draft->status);
        $this->assertEquals(1, $draft->histories()->count());

        // Draft does not automatically publish to JSON
        $this->assertEmpty($product->fresh()->name_translations);

        // 2. Approve and publish
        $published = $product->setTranslation(
            field: 'name',
            locale: 'hy',
            translation: 'Տավարի Տարտար (Ֆիրմային)',
            status: ContentTranslation::STATUS_PUBLISHED,
            sourceType: ContentTranslation::SOURCE_MANUAL
        );

        $this->assertEquals(ContentTranslation::STATUS_PUBLISHED, $published->status);
        $this->assertEquals(2, $published->histories()->count());

        // Published syncs to JSON
        $freshProduct = $product->fresh();
        $this->assertEquals('Տավարի Տարտար (Ֆիրմային)', $freshProduct->name_translations['hy'] ?? null);
    }

    public function test_detects_outdated_translations_when_source_changes(): void
    {
        $vendor = Vendor::create([
            'name' => 'Bistro Hub',
            'slug' => 'bistro-hub-2',
        ]);

        $category = Category::create([
            'vendor_id' => $vendor->id,
            'name' => 'Pizzas',
        ]);

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Margherita Pizza',
            'price' => 3000,
        ]);

        $translation = $product->setTranslation(
            field: 'name',
            locale: 'ru',
            translation: 'Пицца Маргарита',
            status: ContentTranslation::STATUS_PUBLISHED
        );

        $this->assertFalse($translation->fresh()->is_outdated);

        // Update product original source text
        $product->update(['name' => 'Classic Margherita Pizza Extra Cheese']);

        // Translation should now be marked outdated and stale
        $this->assertTrue($translation->fresh()->is_outdated);
        $this->assertEquals(ContentTranslation::STATUS_STALE, $translation->fresh()->status);
    }

    public function test_glossary_scoping(): void
    {
        $vendor = Vendor::create([
            'name' => 'Bistro Hub',
            'slug' => 'bistro-hub-3',
        ]);

        TranslationGlossary::create([
            'vendor_id' => null, // Global
            'term' => 'Dolma',
            'target_locale' => 'ru',
            'translation' => 'Долма',
        ]);

        TranslationGlossary::create([
            'vendor_id' => $vendor->id, // Tenant
            'term' => 'Chef Salad',
            'target_locale' => 'ru',
            'translation' => 'Фирменный салат от шефа',
        ]);

        $terms = TranslationGlossary::forVendorOrGlobal($vendor->id)->forLocale('ru')->pluck('term')->all();

        $this->assertContains('Dolma', $terms);
        $this->assertContains('Chef Salad', $terms);
    }
}
