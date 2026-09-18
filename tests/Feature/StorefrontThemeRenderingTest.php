<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Location;
use App\Models\MenuTemplate;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontThemeRenderingTest extends TestCase
{
    use RefreshDatabase;

    protected Vendor $vendor;
    protected Location $location;
    protected Category $category;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendor = Vendor::create([
            'name' => 'Gourmet House',
            'slug' => 'gourmet-house',
            'email' => 'gourmet@house.am',
            'password' => bcrypt('password'),
            'currency' => 'AMD',
            'is_active' => true,
        ]);

        $this->location = Location::create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Main Hall',
            'slug' => 'main-hall',
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Main Dishes',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $this->category->id,
            'name' => 'Duck Breast',
            'price' => 6500,
            'is_available' => true,
        ]);

        ProductVariation::create([
            'product_id' => $this->product->id,
            'name' => 'Standard',
            'price' => 6500,
            'is_default' => true,
        ]);
    }

    public function test_modern_bistro_theme_renders_and_includes_storefront_scripts(): void
    {
        $tmpl = MenuTemplate::create([
            'name' => 'Modern Bistro',
            'slug' => 'modern-bistro',
            'is_active' => true,
        ]);
        $this->vendor->update(['menu_template_id' => $tmpl->id]);

        $response = $this->get(route('client.menu', ['vendor_slug' => $this->vendor->slug]));

        $response->assertStatus(200);
        $response->assertSee('createStorefrontApp');
        $response->assertSee('modernBistroApp');
        $response->assertSee('Duck Breast');
        $response->assertSee('addSelectedVariationToCart');
        $response->assertSee('submitOrder');
    }

    public function test_luxury_dark_theme_renders_and_includes_storefront_scripts(): void
    {
        $tmpl = MenuTemplate::create([
            'name' => 'Luxury Dark',
            'slug' => 'luxury-dark',
            'is_active' => true,
        ]);
        $this->vendor->update(['menu_template_id' => $tmpl->id]);

        $response = $this->get(route('client.menu', ['vendor_slug' => $this->vendor->slug]));

        $response->assertStatus(200);
        $response->assertSee('createStorefrontApp');
        $response->assertSee('storefrontApp');
        $response->assertSee('Duck Breast');
        $response->assertSee('addSelectedVariationToCart');
        $response->assertSee('submitOrder');
    }

    public function test_vibrant_glass_theme_renders_and_includes_storefront_scripts(): void
    {
        $tmpl = MenuTemplate::create([
            'name' => 'Vibrant Glass',
            'slug' => 'vibrant-glass',
            'is_active' => true,
        ]);
        $this->vendor->update(['menu_template_id' => $tmpl->id]);

        $response = $this->get(route('client.menu', ['vendor_slug' => $this->vendor->slug]));

        $response->assertStatus(200);
        $response->assertSee('createStorefrontApp');
        $response->assertSee('vibrantGlassApp');
        $response->assertSee('Duck Breast');
        $response->assertSee('addSelectedVariationToCart');
        $response->assertSee('submitOrder');
    }
}
