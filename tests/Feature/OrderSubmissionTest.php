<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_submit_order_with_notes(): void
    {
        $vendor = Vendor::create([
            'name' => 'Test Restaurant',
            'slug' => 'test-restaurant',
            'email' => 'test@restaurant.com',
            'password' => bcrypt('password'),
        ]);

        $location = Location::create([
            'vendor_id' => $vendor->id,
            'name' => 'Main Location',
            'slug' => 'main-location',
            'address' => '123 Main St',
            'whatsapp_number' => '37491234567',
        ]);

        $category = Category::create([
            'vendor_id' => $vendor->id,
            'name' => 'Starters',
        ]);

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Hummus',
            'price' => 3200,
        ]);

        $response = $this->postJson(route('client.order.submit', ['vendor_slug' => $vendor->slug]), [
            'location_id' => $location->id,
            'table_number' => 'Table 4',
            'type' => 'dine_in',
            'customer_name' => 'John Doe',
            'customer_phone' => '091234567',
            'customer_email' => 'john@example.com',
            'notes' => 'No onions, sauce on the side please',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('orders', [
            'vendor_id' => $vendor->id,
            'notes' => 'No onions, sauce on the side please',
            'customer_name' => 'John Doe',
        ]);
    }

    public function test_can_submit_order_with_product_variations(): void
    {
        $vendor = Vendor::create([
            'name' => 'Steakhouse Vendor',
            'slug' => 'steakhouse',
            'email' => 'steak@house.com',
            'password' => bcrypt('password'),
        ]);

        $location = Location::create([
            'vendor_id' => $vendor->id,
            'name' => 'Downtown Branch',
            'slug' => 'downtown',
            'address' => '456 Northern Ave',
        ]);

        $category = Category::create([
            'vendor_id' => $vendor->id,
            'name' => 'Mains',
        ]);

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Ribeye Steak',
            'price' => 8500,
        ]);

        $variationSmall = ProductVariation::create([
            'product_id' => $product->id,
            'name' => 'Standard Cut (300g)',
            'price' => 8500,
            'is_default' => true,
        ]);

        $variationLarge = ProductVariation::create([
            'product_id' => $product->id,
            'name' => 'Large Cut (500g)',
            'price' => 13500,
            'is_default' => false,
        ]);

        $response = $this->postJson(route('client.order.submit', ['vendor_slug' => $vendor->slug]), [
            'location_id' => $location->id,
            'table_number' => 'Table 7',
            'type' => 'dine_in',
            'customer_name' => 'Steak Lover',
            'items' => [
                [
                    'product_id' => $product->id,
                    'variation_id' => $variationLarge->id,
                    'variation_name' => $variationLarge->name,
                    'quantity' => 1,
                ],
                [
                    'product_id' => $product->id,
                    'variation_id' => $variationSmall->id,
                    'variation_name' => $variationSmall->name,
                    'quantity' => 2,
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('orders', [
            'vendor_id' => $vendor->id,
            'total_amount' => 13500 + (8500 * 2), // 30,500
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'variation_name' => 'Large Cut (500g)',
            'unit_price' => 13500,
            'quantity' => 1,
            'subtotal' => 13500,
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'variation_name' => 'Standard Cut (300g)',
            'unit_price' => 8500,
            'quantity' => 2,
            'subtotal' => 17000,
        ]);
    }

    public function test_can_submit_delivery_order_with_address(): void
    {
        $vendor = Vendor::create([
            'name' => 'Delivery Bistro',
            'slug' => 'delivery-bistro',
            'email' => 'delivery@bistro.com',
            'password' => bcrypt('password'),
        ]);

        $location = Location::create([
            'vendor_id' => $vendor->id,
            'name' => 'Central Hub',
            'slug' => 'central-hub',
            'address' => '100 Main Ave',
            'whatsapp_number' => '37498765432',
        ]);

        $category = Category::create([
            'vendor_id' => $vendor->id,
            'name' => 'Burgers',
        ]);

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Cheeseburger',
            'price' => 2500,
        ]);

        $response = $this->postJson(route('client.order.submit', ['vendor_slug' => $vendor->slug]), [
            'location_id' => $location->id,
            'type' => 'delivery',
            'delivery_address' => 'Yerevan, Sayat-Nova 15, apt. 12',
            'customer_name' => 'Karen Hakobyan',
            'customer_phone' => '093556677',
            'customer_email' => 'karen@example.com',
            'customer_birthdate' => '1995-08-20',
            'notes' => 'Please ring the intercom 12',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('orders', [
            'vendor_id' => $vendor->id,
            'type' => 'delivery',
            'table_number' => null,
            'delivery_address' => 'Yerevan, Sayat-Nova 15, apt. 12',
            'customer_name' => 'Karen Hakobyan',
            'customer_phone' => '093556677',
            'customer_birthdate' => '1995-08-20',
        ]);

        $this->assertDatabaseHas('customers', [
            'vendor_id' => $vendor->id,
            'name' => 'Karen Hakobyan',
            'phone' => '093556677',
            'address' => 'Yerevan, Sayat-Nova 15, apt. 12',
        ]);

        $createdCustomer = Customer::where('phone', '093556677')->first();
        $this->assertNotNull($createdCustomer);
        $this->assertEquals('1995-08-20', $createdCustomer->birthdate?->format('Y-m-d'));
    }

    public function test_delivery_order_requires_address_and_phone(): void
    {
        $vendor = Vendor::create([
            'name' => 'Validation Bistro',
            'slug' => 'validation-bistro',
            'email' => 'val@bistro.com',
            'password' => bcrypt('password'),
        ]);

        $location = Location::create([
            'vendor_id' => $vendor->id,
            'name' => 'Branch 1',
            'slug' => 'branch-1',
        ]);

        $category = Category::create([
            'vendor_id' => $vendor->id,
            'name' => 'Drinks',
        ]);

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Fresh Lemonade',
            'price' => 1200,
        ]);

        // Attempt delivery order without address and without phone
        $response = $this->postJson(route('client.order.submit', ['vendor_slug' => $vendor->slug]), [
            'location_id' => $location->id,
            'type' => 'delivery',
            'customer_name' => 'No Address User',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['delivery_address', 'customer_phone']);
    }

    public function test_service_fee_applied_correctly_for_dine_in_orders(): void
    {
        $vendor = Vendor::create([
            'name' => 'Service Fee Bistro',
            'slug' => 'service-fee-bistro',
            'email' => 'fee@bistro.com',
            'password' => bcrypt('password'),
            'currency' => 'AMD',
            'service_fee_enabled' => true,
            'service_fee_type' => 'percent',
            'service_fee_value' => 10,
            'service_fee_min_order' => 2000,
        ]);

        $location = Location::create([
            'vendor_id' => $vendor->id,
            'name' => 'Center Branch',
            'slug' => 'center-branch',
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

        // Case A: 2 x 3000 = 6000 AMD, qualifies for 10% service fee (+600 AMD) -> 6600 AMD
        $response = $this->postJson(route('client.order.submit', ['vendor_slug' => $vendor->slug]), [
            'location_id' => $location->id,
            'table_number' => 'Table 5',
            'type' => 'dine_in',
            'customer_name' => 'Armen Sargsyan',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('orders', [
            'vendor_id' => $vendor->id,
            'customer_name' => 'Armen Sargsyan',
            'subtotal' => 6000.00,
            'service_fee' => 600.00,
            'delivery_fee' => 0.00,
            'total_amount' => 6600.00,
        ]);
    }

    public function test_delivery_fee_and_free_delivery_threshold(): void
    {
        $vendor = Vendor::create([
            'name' => 'Delivery Bistro',
            'slug' => 'delivery-bistro',
            'email' => 'delivery@bistro.com',
            'password' => bcrypt('password'),
            'currency' => 'AMD',
            'delivery_enabled' => true,
            'delivery_fee' => 800,
            'delivery_min_amount' => 2000,
            'delivery_free_from' => 5000,
        ]);

        $location = Location::create([
            'vendor_id' => $vendor->id,
            'name' => 'Delivery Hub',
            'slug' => 'delivery-hub',
        ]);

        $category = Category::create([
            'vendor_id' => $vendor->id,
            'name' => 'Burgers',
        ]);

        $burger = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Classic Burger',
            'price' => 2500,
        ]);

        // Case 1: 1 x 2500 = 2500 AMD (above min_amount 2000, below free_from 5000) -> delivery fee 800 -> total 3300
        $response1 = $this->postJson(route('client.order.submit', ['vendor_slug' => $vendor->slug]), [
            'location_id' => $location->id,
            'type' => 'delivery',
            'customer_name' => 'Vahagn',
            'customer_phone' => '099112233',
            'delivery_address' => 'Komitas 45',
            'items' => [
                [
                    'product_id' => $burger->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response1->assertStatus(200);
        $this->assertDatabaseHas('orders', [
            'vendor_id' => $vendor->id,
            'customer_name' => 'Vahagn',
            'subtotal' => 2500.00,
            'delivery_fee' => 800.00,
            'total_amount' => 3300.00,
        ]);

        // Case 2: 3 x 2500 = 7500 AMD (above free_from 5000) -> free delivery 0 AMD -> total 7500
        $response2 = $this->postJson(route('client.order.submit', ['vendor_slug' => $vendor->slug]), [
            'location_id' => $location->id,
            'type' => 'delivery',
            'customer_name' => 'Ani',
            'customer_phone' => '099445566',
            'delivery_address' => 'Baghramyan 12',
            'items' => [
                [
                    'product_id' => $burger->id,
                    'quantity' => 3,
                ],
            ],
        ]);

        $response2->assertStatus(200);
        $this->assertDatabaseHas('orders', [
            'vendor_id' => $vendor->id,
            'customer_name' => 'Ani',
            'subtotal' => 7500.00,
            'delivery_fee' => 0.00,
            'total_amount' => 7500.00,
        ]);
    }

    public function test_delivery_order_fails_when_below_minimum_amount(): void
    {
        $vendor = Vendor::create([
            'name' => 'Min Bistro',
            'slug' => 'min-bistro',
            'email' => 'min@bistro.com',
            'password' => bcrypt('password'),
            'currency' => 'AMD',
            'delivery_enabled' => true,
            'delivery_min_amount' => 5000,
        ]);

        $location = Location::create([
            'vendor_id' => $vendor->id,
            'name' => 'Location Hub',
            'slug' => 'location-hub',
        ]);

        $category = Category::create([
            'vendor_id' => $vendor->id,
            'name' => 'Coffee',
        ]);

        $coffee = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Espresso',
            'price' => 1000,
        ]);

        // 1 x 1000 = 1000 AMD, which is below delivery_min_amount (5000 AMD)
        $response = $this->postJson(route('client.order.submit', ['vendor_slug' => $vendor->slug]), [
            'location_id' => $location->id,
            'type' => 'delivery',
            'customer_name' => 'Low Order Buyer',
            'customer_phone' => '077889900',
            'delivery_address' => 'Tumanyan 5',
            'items' => [
                [
                    'product_id' => $coffee->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
    }
}
