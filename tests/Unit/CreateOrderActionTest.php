<?php

namespace Tests\Unit;

use App\Actions\CreateOrderAction;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Vendor;
use App\Services\CustomerSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateOrderActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_order_action_calculates_total_and_creates_items(): void
    {
        $vendor = Vendor::create([
            'name' => 'Pizza Bella',
            'slug' => 'pizza-bella',
            'email' => 'bella@pizza.com',
            'password' => bcrypt('secret'),
            'currency' => 'AMD',
        ]);

        $location = Location::create([
            'vendor_id' => $vendor->id,
            'name' => 'Central Branch',
            'slug' => 'central',
            'whatsapp_number' => '+37498111222',
        ]);

        $category = Category::create([
            'vendor_id' => $vendor->id,
            'name' => 'Pizzas',
        ]);

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Margherita',
            'price' => 2500,
        ]);

        $variationLarge = ProductVariation::create([
            'product_id' => $product->id,
            'name' => 'Large (36cm)',
            'price' => 3800,
            'is_default' => false,
        ]);

        $action = new CreateOrderAction(new CustomerSyncService());

        $payload = [
            'location_id' => $location->id,
            'table_number' => 'Table 5',
            'type' => 'dine_in',
            'customer_name' => 'Aram',
            'customer_phone' => '098123456',
            'customer_email' => 'aram@example.com',
            'marketing_opt_in' => true,
            'notes' => 'Extra basil',
            'items' => [
                [
                    'product_id' => $product->id,
                    'variation_id' => $variationLarge->id,
                    'variation_name' => $variationLarge->name,
                    'quantity' => 2,
                ]
            ]
        ];

        $result = $action->execute($vendor, $payload);

        $order = $result['order'];
        $this->assertNotNull($order);
        $this->assertEquals(7600, $order->total_amount); // 3800 * 2
        $this->assertEquals('Table 5', $order->table_number);
        $this->assertEquals('Extra basil', $order->notes);

        // Check OrderItem
        $this->assertCount(1, $order->items);
        $this->assertEquals('Large (36cm)', $order->items->first()->variation_name);
        $this->assertEquals(3800, $order->items->first()->unit_price);
        $this->assertEquals(7600, $order->items->first()->subtotal);

        // Check Customer
        $customer = Customer::where('phone', '098123456')->first();
        $this->assertNotNull($customer);
        $this->assertEquals('Aram', $customer->name);
        $this->assertEquals(1, $customer->total_orders_count);
        $this->assertEquals(7600, $customer->total_spent);

        // Check WhatsApp URL
        $this->assertNotNull($result['whatsapp_url']);
        $this->assertStringContainsString('37498111222', $result['whatsapp_url']);
        $this->assertStringContainsString('Margherita', $result['whatsapp_url']);
    }
}
