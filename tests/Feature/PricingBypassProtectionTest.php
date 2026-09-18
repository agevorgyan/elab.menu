<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Location;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingBypassProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected Vendor $vendor;
    protected Location $location;
    protected Category $category;
    protected Product $steakProduct;
    protected ProductVariation $variationStandard;
    protected ProductVariation $variationLarge;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendor = Vendor::create([
            'name' => 'Steakhouse Bistro',
            'slug' => 'steakhouse',
            'email' => 'info@steakhouse.am',
            'password' => bcrypt('password'),
            'subscription_plan' => 'pro',
            'currency' => 'AMD',
            'is_active' => true,
        ]);

        $this->location = Location::create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Main Dining',
            'slug' => 'main-dining',
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Steaks & Grills',
            'sort_order' => 1,
        ]);

        // Product with base price 8,500 AMD and 2 portions
        $this->steakProduct = Product::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $this->category->id,
            'name' => 'Ribeye Steak',
            'price' => 8500,
        ]);

        $this->variationStandard = ProductVariation::create([
            'product_id' => $this->steakProduct->id,
            'name' => 'Standard Cut (300g)',
            'price' => 8500,
            'is_default' => true,
        ]);

        $this->variationLarge = ProductVariation::create([
            'product_id' => $this->steakProduct->id,
            'name' => 'Large Cut (550g)',
            'price' => 14000,
            'is_default' => false,
        ]);
    }

    public function test_ordering_expensive_variation_charges_variation_price_not_base_price(): void
    {
        $response = $this->postJson(route('client.order.submit', ['vendor_slug' => $this->vendor->slug]), [
            'location_id' => $this->location->id,
            'type' => 'dine_in',
            'table_number' => 'Table 7',
            'customer_name' => 'Steak Lover',
            'items' => [
                [
                    'product_id' => $this->steakProduct->id,
                    'variation_id' => $this->variationLarge->id,
                    'quantity' => 2,
                ]
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Total must be 14,000 * 2 = 28,000 AMD (NOT 8,500 * 2 = 17,000 AMD)
        $order = Order::where('vendor_id', $this->vendor->id)->first();
        $this->assertNotNull($order);
        $this->assertEquals(28000, $order->total_amount);

        $orderItem = $order->items->first();
        $this->assertEquals('Large Cut (550g)', $orderItem->variation_name);
        $this->assertEquals(14000, $orderItem->unit_price);
        $this->assertEquals(28000, $orderItem->subtotal);
    }

    public function test_cannot_bypass_variation_price_by_omitting_variation_on_multi_variation_product(): void
    {
        // Malicious user attempts to order multi-variation product without specifying variation
        $response = $this->postJson(route('client.order.submit', ['vendor_slug' => $this->vendor->slug]), [
            'location_id' => $this->location->id,
            'type' => 'dine_in',
            'customer_name' => 'Cheater',
            'items' => [
                [
                    'product_id' => $this->steakProduct->id,
                    // No variation_id and no variation_name!
                    'quantity' => 1,
                ]
            ]
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items']);

        // Assert NO orders were created
        $this->assertDatabaseMissing('orders', [
            'vendor_id' => $this->vendor->id,
            'customer_name' => 'Cheater',
        ]);
    }

    public function test_cannot_bypass_pricing_with_spoofed_or_cross_product_variation_id(): void
    {
        // Another cheap product
        $cheapProduct = Product::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $this->category->id,
            'name' => 'Garden Salad',
            'price' => 1200,
        ]);

        $cheapVariation = ProductVariation::create([
            'product_id' => $cheapProduct->id,
            'name' => 'Small Salad',
            'price' => 1200,
            'is_default' => true,
        ]);

        // Malicious user attempts to order Ribeye Steak with Garden Salad's variation_id (1200 AMD)
        $response = $this->postJson(route('client.order.submit', ['vendor_slug' => $this->vendor->slug]), [
            'location_id' => $this->location->id,
            'type' => 'dine_in',
            'customer_name' => 'Attacker',
            'items' => [
                [
                    'product_id' => $this->steakProduct->id,
                    'variation_id' => $cheapVariation->id, // Belongs to Garden Salad!
                    'quantity' => 1,
                ]
            ]
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items']);

        $this->assertDatabaseMissing('orders', [
            'vendor_id' => $this->vendor->id,
            'customer_name' => 'Attacker',
        ]);
    }

    public function test_cannot_bypass_pricing_with_fabricated_variation_name(): void
    {
        // Malicious user tries to send arbitrary custom variation name without valid database variation
        $response = $this->postJson(route('client.order.submit', ['vendor_slug' => $this->vendor->slug]), [
            'location_id' => $this->location->id,
            'type' => 'dine_in',
            'customer_name' => 'Attacker',
            'items' => [
                [
                    'product_id' => $this->steakProduct->id,
                    'variation_name' => 'Free Wagyu 1kg Portion',
                    'quantity' => 1,
                ]
            ]
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items']);

        $this->assertDatabaseMissing('orders', [
            'vendor_id' => $this->vendor->id,
            'customer_name' => 'Attacker',
        ]);
    }

    public function test_valid_variation_name_resolves_correct_variation_price(): void
    {
        $response = $this->postJson(route('client.order.submit', ['vendor_slug' => $this->vendor->slug]), [
            'location_id' => $this->location->id,
            'type' => 'dine_in',
            'customer_name' => 'Name Selector',
            'items' => [
                [
                    'product_id' => $this->steakProduct->id,
                    'variation_name' => 'Large Cut (550g)',
                    'quantity' => 1,
                ]
            ]
        ]);

        $response->assertStatus(200);

        $order = Order::where('vendor_id', $this->vendor->id)->first();
        $this->assertEquals(14000, $order->total_amount);
        $this->assertEquals(14000, $order->items->first()->unit_price);
    }

    public function test_multi_variation_order_with_different_variations_calculates_correct_totals(): void
    {
        // 1x Standard (8500) + 2x Large (14000 * 2 = 28000) = 36,500 AMD
        $response = $this->postJson(route('client.order.submit', ['vendor_slug' => $this->vendor->slug]), [
            'location_id' => $this->location->id,
            'type' => 'dine_in',
            'customer_name' => 'Table Party',
            'items' => [
                [
                    'product_id' => $this->steakProduct->id,
                    'variation_id' => $this->variationStandard->id,
                    'quantity' => 1,
                ],
                [
                    'product_id' => $this->steakProduct->id,
                    'variation_id' => $this->variationLarge->id,
                    'quantity' => 2,
                ]
            ]
        ]);

        $response->assertStatus(200);

        $order = Order::where('vendor_id', $this->vendor->id)->first();
        $this->assertEquals(36500, $order->total_amount);
        $this->assertCount(2, $order->items);

        $stdItem = $order->items->where('variation_name', 'Standard Cut (300g)')->first();
        $this->assertEquals(8500, $stdItem->unit_price);
        $this->assertEquals(8500, $stdItem->subtotal);

        $largeItem = $order->items->where('variation_name', 'Large Cut (550g)')->first();
        $this->assertEquals(14000, $largeItem->unit_price);
        $this->assertEquals(28000, $largeItem->subtotal);
    }

    public function test_single_variation_product_auto_resolves_variation_price(): void
    {
        $singleVarProduct = Product::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $this->category->id,
            'name' => 'Special Soup',
            'price' => 2500,
        ]);

        ProductVariation::create([
            'product_id' => $singleVarProduct->id,
            'name' => 'Standard Bowl',
            'price' => 3200, // Variation price differs from product base price
            'is_default' => true,
        ]);

        // Submit without variation_id
        $response = $this->postJson(route('client.order.submit', ['vendor_slug' => $this->vendor->slug]), [
            'location_id' => $this->location->id,
            'type' => 'dine_in',
            'customer_name' => 'Soup Eater',
            'items' => [
                [
                    'product_id' => $singleVarProduct->id,
                    'quantity' => 1,
                ]
            ]
        ]);

        $response->assertStatus(200);

        $order = Order::where('vendor_id', $this->vendor->id)->first();
        $this->assertEquals(3200, $order->total_amount);
        $this->assertEquals(3200, $order->items->first()->unit_price);
        $this->assertEquals('Standard Bowl', $order->items->first()->variation_name);
    }
}
