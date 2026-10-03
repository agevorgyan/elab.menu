<?php

namespace Tests\Unit;

use App\DTOs\OrderItemDTO;
use App\Models\Category;
use App\Models\Location;
use App\Models\LocationProductOverride;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Vendor;
use App\Services\OrderPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrderPricingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Vendor $vendor;

    protected Location $location;

    protected Category $category;

    protected Product $product;

    protected OrderPricingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendor = Vendor::create([
            'name' => 'Bistro Gourmet',
            'slug' => 'bistro-gourmet',
            'email' => 'bistro@example.com',
            'password' => bcrypt('secret'),
            'delivery_enabled' => true,
            'delivery_fee' => 500,
            'delivery_free_from' => 5000,
        ]);

        $this->location = Location::create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Downtown',
            'slug' => 'downtown',
        ]);

        $this->category = Category::create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Pizzas',
        ]);

        $this->product = Product::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $this->category->id,
            'name' => 'Pizza Margherita',
            'price' => 3000,
            'is_available' => true,
        ]);

        $this->service = new OrderPricingService;
    }

    public function test_matches_variation_by_id(): void
    {
        $small = ProductVariation::create([
            'product_id' => $this->product->id,
            'name' => 'Small (25cm)',
            'price' => 2500,
        ]);
        $large = ProductVariation::create([
            'product_id' => $this->product->id,
            'name' => 'Large (35cm)',
            'price' => 4000,
        ]);

        $itemDTO = new OrderItemDTO(
            productId: $this->product->id,
            quantity: 1,
            variationId: $large->id
        );

        $pricing = $this->service->resolveItemPricing($this->product, $itemDTO, $this->location->id);

        $this->assertEquals(4000.0, $pricing['unit_price']);
        $this->assertEquals('Large (35cm)', $pricing['variation_name']);
    }

    public function test_matches_variation_by_multilingual_name(): void
    {
        ProductVariation::create([
            'product_id' => $this->product->id,
            'name' => 'Regular',
            'name_translations' => [
                'en' => 'Regular',
                'hy' => 'Ստանդարտ',
                'fr' => 'Moyenne',
            ],
            'price' => 3200,
        ]);

        $itemDTO = new OrderItemDTO(
            productId: $this->product->id,
            quantity: 1,
            variationName: 'moyenne'
        );

        $pricing = $this->service->resolveItemPricing($this->product, $itemDTO, $this->location->id);

        $this->assertEquals(3200.0, $pricing['unit_price']);
        $this->assertEquals('Regular', $pricing['variation_name']);
    }

    public function test_auto_selects_single_variation_when_none_specified(): void
    {
        ProductVariation::create([
            'product_id' => $this->product->id,
            'name' => 'Standard Portion',
            'price' => 3000,
            'is_default' => true,
        ]);

        $itemDTO = new OrderItemDTO(
            productId: $this->product->id,
            quantity: 1
        );

        $pricing = $this->service->resolveItemPricing($this->product, $itemDTO, $this->location->id);

        $this->assertEquals(3000.0, $pricing['unit_price']);
        $this->assertEquals('Standard Portion', $pricing['variation_name']);
    }

    public function test_multi_variation_requires_explicit_variation(): void
    {
        ProductVariation::create([
            'product_id' => $this->product->id,
            'name' => 'Small',
            'price' => 2000,
        ]);
        ProductVariation::create([
            'product_id' => $this->product->id,
            'name' => 'Large',
            'price' => 3500,
        ]);

        $itemDTO = new OrderItemDTO(
            productId: $this->product->id,
            quantity: 1
        );

        $this->expectException(ValidationException::class);
        $this->service->resolveItemPricing($this->product, $itemDTO, $this->location->id);
    }

    public function test_applies_location_override_to_single_variation_product(): void
    {
        ProductVariation::create([
            'product_id' => $this->product->id,
            'name' => 'Standard Portion',
            'price' => 3000,
            'is_default' => true,
        ]);

        LocationProductOverride::create([
            'vendor_id' => $this->vendor->id,
            'location_id' => $this->location->id,
            'product_id' => $this->product->id,
            'override_price' => 3600,
            'is_available' => true,
        ]);

        $itemDTO = new OrderItemDTO(
            productId: $this->product->id,
            quantity: 1
        );

        $pricing = $this->service->resolveItemPricing($this->product, $itemDTO, $this->location->id);

        $this->assertEquals(3600.0, $pricing['unit_price']);
    }

    public function test_calculate_fees_handles_free_delivery_threshold(): void
    {
        // Below threshold: 4000 < 5000 -> delivery fee is 500
        $feesBelow = $this->service->calculateFees($this->vendor, 4000, 'delivery');
        $this->assertEquals(500.0, $feesBelow['delivery_fee']);
        $this->assertEquals(4500.0, $feesBelow['final_total']);

        // Above threshold: 6000 >= 5000 -> delivery fee is 0
        $feesAbove = $this->service->calculateFees($this->vendor, 6000, 'delivery');
        $this->assertEquals(0.0, $feesAbove['delivery_fee']);
        $this->assertEquals(6000.0, $feesAbove['final_total']);

        // Dine-in order has no delivery fee
        $feesDineIn = $this->service->calculateFees($this->vendor, 4000, 'dine_in');
        $this->assertEquals(0.0, $feesDineIn['delivery_fee']);
    }

    public function test_resolve_item_pricing_does_not_trigger_n_plus_one_product_query(): void
    {
        $v1 = ProductVariation::create([
            'product_id' => $this->product->id,
            'name' => 'Portion A',
            'price' => 3000,
        ]);
        $v2 = ProductVariation::create([
            'product_id' => $this->product->id,
            'name' => 'Portion B',
            'price' => 3500,
        ]);

        // Eager load variations
        $this->product->load(['variations', 'overrides']);

        $itemDTO1 = new OrderItemDTO(productId: $this->product->id, quantity: 1, variationId: $v1->id);
        $itemDTO2 = new OrderItemDTO(productId: $this->product->id, quantity: 1, variationId: $v2->id);

        DB::enableQueryLog();
        DB::flushQueryLog();

        $this->service->resolveItemPricing($this->product, $itemDTO1, $this->location->id);
        $this->service->resolveItemPricing($this->product, $itemDTO2, $this->location->id);

        $queries = DB::getQueryLog();
        $productQueries = array_filter($queries, function ($q) {
            return str_contains($q['query'], '"products"') && ! str_contains($q['query'], 'location_product_overrides');
        });

        $this->assertCount(0, $productQueries, 'Expected zero extra product queries when resolving variation pricing on pre-loaded product');
    }
}
