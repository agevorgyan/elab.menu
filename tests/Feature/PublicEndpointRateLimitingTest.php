<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicEndpointRateLimitingTest extends TestCase
{
    use RefreshDatabase;

    protected Vendor $vendor;

    protected Location $location;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendor = Vendor::create([
            'name' => 'Rate Limit Bistro',
            'slug' => 'rate-limit-bistro',
            'email' => 'ratelimit@bistro.com',
            'password' => bcrypt('password123'),
        ]);

        $this->location = Location::create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Main Branch',
            'slug' => 'main-branch',
            'address' => 'Baghramyan Ave',
        ]);

        $category = Category::create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Appetizers',
        ]);

        $this->product = Product::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $category->id,
            'name' => 'Soup',
            'price' => 1500,
        ]);
    }

    public function test_call_waiter_is_rate_limited_after_15_requests_per_minute(): void
    {
        // 15 requests within 1 minute should be allowed
        for ($i = 1; $i <= 15; $i++) {
            $response = $this->postJson(route('client.waiter.call', ['vendor_slug' => $this->vendor->slug]), [
                'location_id' => $this->location->id,
                'table_number' => 'Table 1',
                'type' => 'call_waiter',
            ]);
            $response->assertStatus(200);
        }

        // The 16th request must be rejected with 429 Too Many Requests
        $response16 = $this->postJson(route('client.waiter.call', ['vendor_slug' => $this->vendor->slug]), [
            'location_id' => $this->location->id,
            'table_number' => 'Table 1',
            'type' => 'call_waiter',
        ]);

        $response16->assertStatus(429);
    }

    public function test_submit_order_is_rate_limited_after_15_requests_per_minute(): void
    {
        // Send 15 order submissions
        for ($i = 1; $i <= 15; $i++) {
            $response = $this->postJson(route('client.order.submit', ['vendor_slug' => $this->vendor->slug]), [
                'location_id' => $this->location->id,
                'table_number' => 'Table '.$i,
                'type' => 'dine_in',
                'customer_name' => 'Spammer Test',
                'customer_phone' => '091234567',
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 1,
                    ],
                ],
            ]);
            $response->assertStatus(200);
        }

        // The 16th submission should trigger rate limit (429)
        $response16 = $this->postJson(route('client.order.submit', ['vendor_slug' => $this->vendor->slug]), [
            'location_id' => $this->location->id,
            'table_number' => 'Table 16',
            'type' => 'dine_in',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response16->assertStatus(429);
    }
}
