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
}
