<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Location;
use App\Models\Vendor;
use App\Services\MenuManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuManagementServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_and_product_lifecycle(): void
    {
        $vendor = Vendor::create([
            'name' => 'Italiano',
            'slug' => 'italiano',
            'email' => 'contact@italiano.com',
            'password' => bcrypt('secret'),
        ]);

        $location = Location::create([
            'vendor_id' => $vendor->id,
            'name' => 'North Branch',
            'slug' => 'north',
        ]);

        $service = new MenuManagementService;

        // 1. Create Category
        $category = $service->createCategory($vendor, [
            'name' => 'Desserts',
            'hy_name' => 'Աղանդերներ',
            'ru_name' => 'Десерты',
            'description' => 'Sweet treats',
        ]);

        $this->assertEquals('Desserts', $category->name);
        $this->assertEquals('Աղանդերներ', $category->name_translations['hy']);
        $this->assertEquals(1, $category->sort_order);

        // 2. Create Product
        $product = $service->createProduct($vendor, [
            'category_id' => $category->id,
            'name' => 'Tiramisu',
            'hy_name' => 'Տիրամիսու',
            'ru_name' => 'Тирамису',
            'price' => 2200,
            'description' => 'Classic Italian coffee cake',
            'calories' => 450,
        ]);

        $this->assertEquals('Tiramisu', $product->name);
        $this->assertEquals(2200, $product->price);
        $this->assertTrue($product->is_available);
        $this->assertCount(1, $product->variations);
        $this->assertEquals('Standard Portion', $product->variations->first()->name);

        // 3. Toggle availability
        $isAvailable = $service->toggleProductAvailability($product);
        $this->assertFalse($isAvailable);
        $this->assertFalse($product->fresh()->is_available);

        // 4. Branch Override
        $override = $service->saveLocationOverride($product, [
            'location_id' => $location->id,
            'override_price' => 2500,
            'is_available' => true,
        ]);

        $this->assertEquals(2500, $override->override_price);
        $this->assertTrue($override->is_available);
        $this->assertEquals(2500, $product->getEffectivePrice($location->id));
    }

    public function test_extract_translations_handles_various_input_formats(): void
    {
        $service = new MenuManagementService;

        // Case 1: Standard inputs with hy_name and ru_name
        $result = $service->extractTranslations([
            'name' => 'Pizza Margherita',
            'hy_name' => 'Պիցցա Մարգարիտա',
            'ru_name' => 'Пицца Маргарита',
        ], 'name');

        $this->assertEquals([
            'en' => 'Pizza Margherita',
            'hy' => 'Պիցցա Մարգարիտա',
            'ru' => 'Пицца Маргарита',
        ], $result);

        // Case 2: Custom language codes via translations array and fallback
        $resultCustom = $service->extractTranslations([
            'description' => 'Delicious pizza',
            'description_translations' => [
                'fr' => ' Pizza délicieuse ',
                'de' => 'Leckere Pizza',
                'hy' => 'Համեղ պիցցա',
            ],
            'ru_description' => 'Вкусная пицца',
        ], 'description');

        $this->assertEquals([
            'en' => 'Delicious pizza',
            'fr' => 'Pizza délicieuse',
            'de' => 'Leckere Pizza',
            'hy' => 'Համեղ պիցցա',
            'ru' => 'Вкусная пицца',
        ], $resultCustom);

        // Case 3: Preserving existing translations when updating
        $existing = ['es' => 'Pizza deliciosa', 'it' => 'Pizza deliziosa'];
        $updated = $service->extractTranslations([
            'name' => 'Updated Pizza',
            'hy_name' => 'Նորացված Պիցցա',
        ], 'name', $existing);

        $this->assertEquals('Updated Pizza', $updated['en']);
        $this->assertEquals('Նորացված Պիցցա', $updated['hy']);
        $this->assertEquals('Pizza deliciosa', $updated['es']);
        $this->assertEquals('Pizza deliziosa', $updated['it']);
    }
}
