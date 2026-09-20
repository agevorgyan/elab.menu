<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Location;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TakeawayOrderTest extends TestCase
{
    use RefreshDatabase;

    protected Vendor $vendor;

    protected Location $location;

    protected Category $category;

    protected Product $product;

    protected User $vendorUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendor = Vendor::create([
            'name' => 'Bistro Yerevan',
            'slug' => 'bistro-yerevan',
            'email' => 'bistro@yerevan.am',
            'currency' => 'AMD',
            'subscription_plan' => 'pro',
            'subscription_expires_at' => now()->addDays(30),
            'is_active' => true,
            'service_fee_enabled' => true,
            'service_fee_type' => 'percent',
            'service_fee_value' => 10.00,
            'delivery_enabled' => true,
            'delivery_fee' => 1000.00,
            'takeaway_enabled' => true,
            'takeaway_min_amount' => 0,
        ]);

        $this->vendorUser = User::factory()->create([
            'vendor_id' => $this->vendor->id,
            'email' => 'owner@bistro.am',
            'role' => 'vendor_owner',
        ]);

        $this->location = Location::create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Cascades Branch',
            'slug' => 'cascades',
            'address' => 'Tamanyan 1',
            'whatsapp_number' => '37491112233',
        ]);

        $this->category = Category::create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Main Dishes',
        ]);

        $this->product = Product::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $this->category->id,
            'name' => 'Grilled Trout',
            'price' => 4500,
            'is_active' => true,
        ]);
    }

    public function test_submitting_dine_in_order_without_table_number_fails_validation(): void
    {
        $response = $this->postJson(route('client.order.submit', ['vendor_slug' => $this->vendor->slug]), [
            'location_id' => $this->location->id,
            'type' => 'dine_in',
            'table_number' => null,
            'customer_name' => 'Guest',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['table_number']);
    }

    public function test_submitting_dine_in_order_with_table_number_succeeds(): void
    {
        $response = $this->postJson(route('client.order.submit', ['vendor_slug' => $this->vendor->slug]), [
            'location_id' => $this->location->id,
            'type' => 'dine_in',
            'table_number' => 'Table 5',
            'customer_name' => 'David',
            'customer_phone' => '091223344',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('orders', [
            'vendor_id' => $this->vendor->id,
            'type' => 'dine_in',
            'table_number' => 'Table 5',
        ]);
    }

    public function test_can_submit_takeaway_order_successfully(): void
    {
        $response = $this->postJson(route('client.order.submit', ['vendor_slug' => $this->vendor->slug]), [
            'location_id' => $this->location->id,
            'type' => 'takeaway',
            'customer_name' => 'Karen',
            'customer_phone' => '093556677',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $order = Order::where('vendor_id', $this->vendor->id)->latest()->first();
        $this->assertNotNull($order);
        $this->assertSame('takeaway', $order->type);
        $this->assertNull($order->table_number);
        $this->assertSame('093556677', $order->customer_phone);
        $this->assertSame(9000.0, (float) $order->subtotal);
        $this->assertSame(0.0, (float) $order->service_fee);
        $this->assertSame(0.0, (float) $order->delivery_fee);
        $this->assertSame(9000.0, (float) $order->total_amount);

        // Verify WhatsApp URL mentions takeaway
        $whatsappUrl = $response->json('whatsapp_url');
        $this->assertNotNull($whatsappUrl);
        $decodedUrl = urldecode($whatsappUrl);
        $this->assertStringContainsString('ՏԵՂՈՒՄ ՎԵՐՑՆԵԼ (Takeaway)', $decodedUrl);
        $this->assertStringContainsString('Cascades Branch', $decodedUrl);
    }

    public function test_takeaway_order_requires_phone_number(): void
    {
        $response = $this->postJson(route('client.order.submit', ['vendor_slug' => $this->vendor->slug]), [
            'location_id' => $this->location->id,
            'type' => 'takeaway',
            'customer_name' => 'Karen',
            'customer_phone' => null,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['customer_phone']);
    }

    public function test_takeaway_order_fails_when_takeaway_is_disabled_by_vendor(): void
    {
        $this->vendor->update(['takeaway_enabled' => false]);

        $response = $this->postJson(route('client.order.submit', ['vendor_slug' => $this->vendor->slug]), [
            'location_id' => $this->location->id,
            'type' => 'takeaway',
            'customer_name' => 'Karen',
            'customer_phone' => '093556677',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['takeaway']);
    }

    public function test_takeaway_order_fails_when_below_min_amount(): void
    {
        $this->vendor->update(['takeaway_min_amount' => 10000]);

        // Product is 4500 AMD, below 10000 AMD
        $response = $this->postJson(route('client.order.submit', ['vendor_slug' => $this->vendor->slug]), [
            'location_id' => $this->location->id,
            'type' => 'takeaway',
            'customer_name' => 'Karen',
            'customer_phone' => '093556677',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['takeaway']);
    }

    public function test_takeaway_does_not_incur_service_fee_or_delivery_fee(): void
    {
        $this->vendor->update([
            'service_fee_enabled' => true,
            'service_fee_type' => 'percent',
            'service_fee_value' => 10.00,
            'delivery_enabled' => true,
            'delivery_fee' => 1500.00,
        ]);

        $response = $this->postJson(route('client.order.submit', ['vendor_slug' => $this->vendor->slug]), [
            'location_id' => $this->location->id,
            'type' => 'takeaway',
            'customer_name' => 'Hasmik',
            'customer_phone' => '098112233',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertStatus(200);

        $order = Order::where('vendor_id', $this->vendor->id)->latest()->first();
        $this->assertSame(4500.0, (float) $order->subtotal);
        $this->assertSame(0.0, (float) $order->service_fee);
        $this->assertSame(0.0, (float) $order->delivery_fee);
        $this->assertSame(4500.0, (float) $order->total_amount);
    }

    public function test_vendor_can_update_takeaway_settings_in_admin(): void
    {
        $response = $this->actingAs($this->vendorUser)->post(route('admin.settings.update'), [
            'service_fee_enabled' => 0,
            'service_fee_type' => 'percent',
            'service_fee_value' => 10,
            'delivery_enabled' => 1,
            'delivery_fee' => 500,
            'delivery_min_amount' => 2000,
            'takeaway_enabled' => 1,
            'takeaway_min_amount' => 3500,
        ]);

        $response->assertRedirect();
        $this->vendor->refresh();
        $this->assertTrue($this->vendor->takeaway_enabled);
        $this->assertSame(3500.0, (float) $this->vendor->takeaway_min_amount);
    }

    public function test_storefront_renders_takeaway_and_differentiates_table_qr(): void
    {
        // Without table QR:
        $responseWithoutTable = $this->get(route('client.menu', [
            'vendor_slug' => $this->vendor->slug,
            'location_slug' => $this->location->slug,
        ]));

        $responseWithoutTable->assertStatus(200);
        $responseWithoutTable->assertSee('isTableFixed: false', false);
        $responseWithoutTable->assertSee("orderType: 'takeaway'", false);

        // With table QR:
        $responseWithTable = $this->get(route('client.menu', [
            'vendor_slug' => $this->vendor->slug,
            'location_slug' => $this->location->slug,
            'table' => '5',
        ]));

        $responseWithTable->assertStatus(200);
        $responseWithTable->assertSee('isTableFixed: true', false);
        $responseWithTable->assertSee("orderType: 'dine_in'", false);
    }
}
