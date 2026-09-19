<?php

namespace Tests\Feature;

use App\Events\OrderCreated;
use App\Events\OrderStatusUpdated;
use App\Models\Category;
use App\Models\Location;
use App\Models\Order;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class OrderAppendTest extends TestCase
{
    use RefreshDatabase;

    protected Vendor $vendor;

    protected Location $location;

    protected Category $category;

    protected Product $product1;

    protected Product $product2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendor = Vendor::create([
            'name' => 'Cascades Bistro',
            'slug' => 'cascades-bistro',
            'email' => 'bistro@cascades.am',
            'password' => bcrypt('password'),
            'currency' => 'AMD',
            'service_fee_enabled' => true,
            'service_fee_type' => 'percent',
            'service_fee_value' => 10,
        ]);

        $this->location = Location::create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Cascades Hall',
            'slug' => 'cascades-hall',
            'address' => '10 Tamanyan St',
            'whatsapp_number' => '37491999999',
        ]);

        $this->category = Category::create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Main Dishes',
        ]);

        $this->product1 = Product::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $this->category->id,
            'name' => 'Khachapuri Adjaruli',
            'price' => 2500,
        ]);

        $this->product2 = Product::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $this->category->id,
            'name' => 'Greek Salad',
            'price' => 2000,
        ]);
    }

    public function test_first_order_creates_new_order(): void
    {
        $response = $this->postJson(route('client.order.submit', ['vendor_slug' => $this->vendor->slug]), [
            'location_id' => $this->location->id,
            'table_number' => 'Table 3',
            'type' => 'dine_in',
            'customer_name' => 'Aram',
            'customer_phone' => '091000000',
            'items' => [
                [
                    'product_id' => $this->product1->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'is_appended' => false,
                'status' => 'pending',
            ]);

        $orderNumber = $response->json('order_number');
        $this->assertNotEmpty($orderNumber);
        $this->assertEquals(1, Order::count());

        $order = Order::first();
        $this->assertEquals(2500, $order->subtotal);
        $this->assertEquals(250, $order->service_fee); // 10%
        $this->assertEquals(2750, $order->total_amount);
    }

    public function test_subsequent_order_with_active_order_number_appends_to_existing_order(): void
    {
        Event::fake([OrderCreated::class, OrderStatusUpdated::class]);

        // 1. Initial Order
        $res1 = $this->postJson(route('client.order.submit', ['vendor_slug' => $this->vendor->slug]), [
            'location_id' => $this->location->id,
            'table_number' => 'Table 3',
            'type' => 'dine_in',
            'customer_name' => 'Aram',
            'notes' => 'Crispy bread',
            'items' => [
                [
                    'product_id' => $this->product1->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $res1->assertStatus(200);
        $orderNumber = $res1->json('order_number');

        // 2. Customer orders second dish while first order is still open
        $res2 = $this->postJson(route('client.order.submit', ['vendor_slug' => $this->vendor->slug]), [
            'location_id' => $this->location->id,
            'table_number' => 'Table 3',
            'type' => 'dine_in',
            'active_order_number' => $orderNumber,
            'notes' => 'Add olive oil to salad',
            'items' => [
                [
                    'product_id' => $this->product2->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $res2->assertStatus(200)
            ->assertJson([
                'success' => true,
                'is_appended' => true,
                'order_number' => $orderNumber,
            ]);

        // Total orders in database must still be exactly 1!
        $this->assertEquals(1, Order::count());

        $order = Order::first();
        $this->assertEquals($orderNumber, $order->order_number);
        // Subtotal: 1x 2500 + 2x 2000 = 6500
        $this->assertEquals(6500, $order->subtotal);
        // Service fee 10% = 650
        $this->assertEquals(650, $order->service_fee);
        // Total = 7150
        $this->assertEquals(7150, $order->total_amount);

        // Check appended notes
        $this->assertStringContainsString('Crispy bread', $order->notes);
        $this->assertStringContainsString('[Հավելում] Add olive oil to salad', $order->notes);

        // Check order items count
        $this->assertEquals(2, $order->items()->count());

        // Ensure events were broadcast
        Event::assertDispatched(OrderCreated::class);
        Event::assertDispatched(OrderStatusUpdated::class);
    }

    public function test_appending_same_dish_increments_quantity(): void
    {
        // 1. Initial Order: 1x Khachapuri
        $res1 = $this->postJson(route('client.order.submit', ['vendor_slug' => $this->vendor->slug]), [
            'location_id' => $this->location->id,
            'table_number' => 'Table 5',
            'type' => 'dine_in',
            'items' => [
                [
                    'product_id' => $this->product1->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $orderNumber = $res1->json('order_number');

        // 2. Customer orders another 2x Khachapuri
        $res2 = $this->postJson(route('client.order.submit', ['vendor_slug' => $this->vendor->slug]), [
            'location_id' => $this->location->id,
            'table_number' => 'Table 5',
            'type' => 'dine_in',
            'active_order_number' => $orderNumber,
            'items' => [
                [
                    'product_id' => $this->product1->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $res2->assertStatus(200)
            ->assertJson([
                'success' => true,
                'is_appended' => true,
            ]);

        $this->assertEquals(1, Order::count());
        $order = Order::first();

        // Exactly 1 OrderItem row, but quantity is now 3
        $this->assertEquals(1, $order->items()->count());
        $item = $order->items()->first();
        $this->assertEquals(3, $item->quantity);
        $this->assertEquals(7500, $item->subtotal);

        // Order total: 7500 + 750 fee = 8250
        $this->assertEquals(7500, $order->subtotal);
        $this->assertEquals(8250, $order->total_amount);
    }

    public function test_closed_or_cancelled_order_is_not_appended_and_creates_new_order(): void
    {
        // 1. Initial Order
        $res1 = $this->postJson(route('client.order.submit', ['vendor_slug' => $this->vendor->slug]), [
            'location_id' => $this->location->id,
            'table_number' => 'Table 1',
            'type' => 'dine_in',
            'items' => [
                [
                    'product_id' => $this->product1->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $orderNumber = $res1->json('order_number');
        $order1 = Order::where('order_number', $orderNumber)->first();

        // Mark order1 as completed (closed)
        $order1->update(['status' => 'completed']);

        // 2. Customer orders again passing old completed order number
        $res2 = $this->postJson(route('client.order.submit', ['vendor_slug' => $this->vendor->slug]), [
            'location_id' => $this->location->id,
            'table_number' => 'Table 1',
            'type' => 'dine_in',
            'active_order_number' => $orderNumber,
            'items' => [
                [
                    'product_id' => $this->product2->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $res2->assertStatus(200)
            ->assertJson([
                'success' => true,
                'is_appended' => false,
            ]);

        // A new, distinct order should have been created!
        $this->assertEquals(2, Order::count());
        $this->assertNotEquals($orderNumber, $res2->json('order_number'));
    }
}
