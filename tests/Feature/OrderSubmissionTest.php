<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
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
                ]
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('orders', [
            'vendor_id' => $vendor->id,
            'notes' => 'No onions, sauce on the side please',
            'customer_name' => 'John Doe',
        ]);
    }
}
