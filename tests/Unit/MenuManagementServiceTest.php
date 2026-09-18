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

        $service = new MenuManagementService();

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
}
