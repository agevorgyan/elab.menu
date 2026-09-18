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

class CrossTenantOrderInjectionTest extends TestCase
{
    use RefreshDatabase;

    protected Vendor $vendor1;
    protected Location $location1;
    protected Product $product1;

    protected Vendor $vendor2;
    protected Location $location2;
    protected Product $product2;

    protected function setUp(): void
    {
        parent::setUp();

        // Vendor 1
        $this->vendor1 = Vendor::create([
            'name' => 'Italian Trattoria',
            'slug' => 'trattoria',
            'email' => 'trattoria@test.com',
            'password' => bcrypt('password'),
            'subscription_plan' => 'pro',
            'is_active' => true,
        ]);

        $this->location1 = Location::create([
            'vendor_id' => $this->vendor1->id,
            'name' => 'Trattoria Downtown',
            'slug' => 'downtown',
            'is_active' => true,
        ]);

        $cat1 = Category::create([
            'vendor_id' => $this->vendor1->id,
            'name' => 'Pastas',
            'sort_order' => 1,
        ]);

        $this->product1 = Product::create([
            'vendor_id' => $this->vendor1->id,
            'category_id' => $cat1->id,
            'name' => 'Carbonara',
            'price' => 3000,
        ]);

        // Vendor 2
        $this->vendor2 = Vendor::create([
            'name' => 'Sushi Bar',
            'slug' => 'sushi-bar',
            'email' => 'sushi@test.com',
            'password' => bcrypt('password'),
            'subscription_plan' => 'pro',
            'is_active' => true,
        ]);

        $this->location2 = Location::create([
            'vendor_id' => $this->vendor2->id,
            'name' => 'Sushi Center',
            'slug' => 'center',
            'is_active' => true,
        ]);

        $cat2 = Category::create([
            'vendor_id' => $this->vendor2->id,
            'name' => 'Rolls',
            'sort_order' => 1,
        ]);

        $this->product2 = Product::create([
            'vendor_id' => $this->vendor2->id,
            'category_id' => $cat2->id,
            'name' => 'Philadelphia Roll',
            'price' => 4500,
        ]);
    }

    public function test_cannot_inject_product_of_another_vendor_into_order(): void
    {
        // Malicious user sends Philadelphia Roll (Vendor 2) to Trattoria (Vendor 1)
        $response = $this->postJson(route('client.order.submit', ['vendor_slug' => $this->vendor1->slug]), [
            'location_id' => $this->location1->id,
            'type' => 'dine_in',
            'customer_name' => 'Attacker',
            'items' => [
                [
                    'product_id' => $this->product2->id, // Belongs to Vendor 2!
                    'quantity' => 2,
                ]
            ]
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items.0.product_id']);

        // Assert NO orders were created for Vendor 1
        $this->assertDatabaseMissing('orders', [
            'vendor_id' => $this->vendor1->id,
            'customer_name' => 'Attacker',
        ]);
    }

    public function test_cannot_inject_location_of_another_vendor_into_order(): void
    {
        // Malicious user sends Location 2 (Vendor 2) to Trattoria (Vendor 1)
        $response = $this->postJson(route('client.order.submit', ['vendor_slug' => $this->vendor1->slug]), [
            'location_id' => $this->location2->id, // Belongs to Vendor 2!
            'type' => 'dine_in',
            'customer_name' => 'Attacker',
            'items' => [
                [
                    'product_id' => $this->product1->id,
                    'quantity' => 1,
                ]
            ]
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['location_id']);

        $this->assertDatabaseMissing('orders', [
            'vendor_id' => $this->vendor1->id,
            'customer_name' => 'Attacker',
        ]);
    }

    public function test_valid_order_with_matching_vendor_items_succeeds(): void
    {
        $response = $this->postJson(route('client.order.submit', ['vendor_slug' => $this->vendor1->slug]), [
            'location_id' => $this->location1->id,
            'type' => 'dine_in',
            'table_number' => 'Table 3',
            'customer_name' => 'Legit Customer',
            'items' => [
                [
                    'product_id' => $this->product1->id,
                    'quantity' => 2,
                ]
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('orders', [
            'vendor_id' => $this->vendor1->id,
            'customer_name' => 'Legit Customer',
            'total_amount' => 6000,
        ]);
    }
}
