<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Location;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\User;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductDiscountTest extends TestCase
{
    use RefreshDatabase;

    protected Vendor $vendor;

    protected User $user;

    protected Location $location;

    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendor = Vendor::create([
            'name' => 'Bistro Yerevan',
            'slug' => 'bistro-yerevan',
            'email' => 'contact@bistro.am',
            'password' => bcrypt('password'),
            'subscription_plan' => 'pro',
            'currency' => 'AMD',
            'is_active' => true,
        ]);

        $this->user = User::create([
            'name' => 'Owner',
            'email' => 'owner@bistro.am',
            'password' => bcrypt('password'),
            'vendor_id' => $this->vendor->id,
            'role' => 'vendor_owner',
        ]);

        $this->location = Location::create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Cascades Branch',
            'slug' => 'cascades',
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Main Dishes',
            'sort_order' => 1,
        ]);
    }

    public function test_product_discount_active_during_scheduled_days_and_hours(): void
    {
        // 2026-09-23 is Wednesday. Set time to 14:30 Yerevan time (+04:00)
        Carbon::setTestNow(Carbon::parse('2026-09-23 14:30:00', 'Asia/Yerevan'));

        $product = Product::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $this->category->id,
            'name' => 'T-Bone Steak',
            'price' => 10000,
            'discount_price' => 8000,
            'discount_days' => ['wednesday', 'thursday', 'friday'],
            'discount_start_time' => '12:00',
            'discount_end_time' => '16:00',
            'is_discount_active' => true,
        ]);

        $this->assertTrue($product->isDiscountActive('Asia/Yerevan'));
        $this->assertEquals(8000, $product->getEffectivePrice());
        $this->assertEquals(10000, $product->getRegularPrice());
        $this->assertEquals(20, $product->getDiscountPercentage());

        Carbon::setTestNow();
    }

    public function test_product_discount_inactive_outside_scheduled_hours(): void
    {
        // Wednesday at 18:00 (outside 12:00 - 16:00)
        Carbon::setTestNow(Carbon::parse('2026-09-23 18:00:00', 'Asia/Yerevan'));

        $product = Product::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $this->category->id,
            'name' => 'T-Bone Steak',
            'price' => 10000,
            'discount_price' => 8000,
            'discount_days' => ['wednesday'],
            'discount_start_time' => '12:00',
            'discount_end_time' => '16:00',
            'is_discount_active' => true,
        ]);

        $this->assertFalse($product->isDiscountActive('Asia/Yerevan'));
        $this->assertEquals(10000, $product->getEffectivePrice());

        Carbon::setTestNow();
    }

    public function test_product_discount_inactive_on_unscheduled_days(): void
    {
        // 2026-09-22 is Tuesday at 14:00 (scheduled only for Wednesday)
        Carbon::setTestNow(Carbon::parse('2026-09-22 14:00:00', 'Asia/Yerevan'));

        $product = Product::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $this->category->id,
            'name' => 'T-Bone Steak',
            'price' => 10000,
            'discount_price' => 8000,
            'discount_days' => ['wednesday'],
            'discount_start_time' => '12:00',
            'discount_end_time' => '16:00',
            'is_discount_active' => true,
        ]);

        $this->assertFalse($product->isDiscountActive('Asia/Yerevan'));
        $this->assertEquals(10000, $product->getEffectivePrice());

        Carbon::setTestNow();
    }

    public function test_product_discount_handles_overnight_schedule(): void
    {
        // 2026-09-25 is Friday. Test 23:30 (during 22:00 - 04:00)
        Carbon::setTestNow(Carbon::parse('2026-09-25 23:30:00', 'Asia/Yerevan'));

        $product = Product::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $this->category->id,
            'name' => 'Late Night Cocktail',
            'price' => 4000,
            'discount_price' => 2500,
            'discount_days' => ['friday', 'saturday'],
            'discount_start_time' => '22:00',
            'discount_end_time' => '04:00',
            'is_discount_active' => true,
        ]);

        $this->assertTrue($product->isDiscountActive('Asia/Yerevan'));
        $this->assertEquals(2500, $product->getEffectivePrice());

        // Friday at 21:00 (before start)
        Carbon::setTestNow(Carbon::parse('2026-09-25 21:00:00', 'Asia/Yerevan'));
        $this->assertFalse($product->isDiscountActive('Asia/Yerevan'));

        Carbon::setTestNow();
    }

    public function test_variation_effective_price_reflects_discount_ratio(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-23 14:30:00', 'Asia/Yerevan'));

        $product = Product::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $this->category->id,
            'name' => 'Steak Platter',
            'price' => 10000,
            'discount_price' => 8000, // 20% off
            'discount_days' => ['wed'],
            'discount_start_time' => '12:00',
            'discount_end_time' => '16:00',
            'is_discount_active' => true,
        ]);

        $variation = ProductVariation::create([
            'product_id' => $product->id,
            'name' => 'Large Cut (500g)',
            'price' => 15000,
        ]);

        // 15000 * (8000 / 10000) = 12000
        $this->assertEquals(12000, $variation->getEffectivePrice());

        Carbon::setTestNow();
    }

    public function test_discount_schedule_summary_generation(): void
    {
        $product = Product::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $this->category->id,
            'name' => 'Burger Combo',
            'price' => 5000,
            'discount_price' => 3500,
            'discount_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'discount_start_time' => '12:00',
            'discount_end_time' => '15:00',
            'is_discount_active' => true,
        ]);

        $summaryHy = $product->getDiscountScheduleSummary('hy');
        $this->assertStringContainsString('Երկ-Ուրբ', $summaryHy);
        $this->assertStringContainsString('12:00 - 15:00', $summaryHy);
    }

    public function test_admin_can_create_product_with_discount_schedule(): void
    {
        $response = $this->actingAs($this->user)
            ->from(route('admin.menu.index'))
            ->post(route('admin.menu.products.store'), [
                'category_id' => $this->category->id,
                'name' => 'Armenian Khorovats',
                'price' => 6000,
                'discount_price' => 4500,
                'discount_days' => ['mon', 'fri', 'sat'],
                'discount_start_time' => '13:00',
                'discount_end_time' => '17:00',
                'is_discount_active' => 1,
            ]);

        $response->assertRedirect(route('admin.menu.index'));

        $this->assertDatabaseHas('products', [
            'vendor_id' => $this->vendor->id,
            'name' => 'Armenian Khorovats',
            'price' => 6000,
            'discount_price' => 4500,
            'discount_start_time' => '13:00',
            'discount_end_time' => '17:00',
            'is_discount_active' => 1,
        ]);
    }

    public function test_admin_can_update_product_discount_schedule(): void
    {
        $product = Product::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $this->category->id,
            'name' => 'Pizza Margherita',
            'price' => 3500,
        ]);

        $response = $this->actingAs($this->user)
            ->from(route('admin.menu.index'))
            ->post(route('admin.menu.products.update', $product->id), [
                'name' => 'Pizza Margherita Updated',
                'category_id' => $this->category->id,
                'price' => 3500,
                'discount_price' => 2800,
                'discount_days' => ['sat', 'sun'],
                'discount_start_time' => '10:00',
                'discount_end_time' => '22:00',
                'is_discount_active' => 1,
            ]);

        $response->assertRedirect(route('admin.menu.index'));

        $product->refresh();
        $this->assertEquals(2800, $product->discount_price);
        $this->assertEquals(['sat', 'sun'], $product->discount_days);
        $this->assertEquals('10:00', $product->discount_start_time);
        $this->assertEquals('22:00', $product->discount_end_time);
        $this->assertTrue((bool) $product->is_discount_active);
    }

    public function test_order_creation_charges_discount_price_when_discount_active(): void
    {
        // Wednesday at 14:00
        Carbon::setTestNow(Carbon::parse('2026-09-23 14:00:00', 'Asia/Yerevan'));

        $product = Product::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $this->category->id,
            'name' => 'Chicken Wrap',
            'price' => 2500,
            'discount_price' => 1900,
            'discount_days' => ['wednesday'],
            'discount_start_time' => '12:00',
            'discount_end_time' => '16:00',
            'is_discount_active' => true,
        ]);

        $response = $this->postJson(route('client.order.submit', ['vendor_slug' => $this->vendor->slug]), [
            'location_id' => $this->location->id,
            'type' => 'dine_in',
            'table_number' => 'Table 4',
            'customer_name' => 'Anahit',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $order = Order::where('vendor_id', $this->vendor->id)->latest()->first();
        $this->assertNotNull($order);
        // 1900 * 2 = 3800 AMD
        $this->assertEquals(3800, $order->total_amount);

        $orderItem = $order->items->first();
        $this->assertEquals(1900, $orderItem->unit_price);
        $this->assertEquals(3800, $orderItem->subtotal);

        Carbon::setTestNow();
    }

    public function test_order_creation_charges_regular_price_when_discount_inactive(): void
    {
        // Wednesday at 19:00 (outside 12:00 - 16:00)
        Carbon::setTestNow(Carbon::parse('2026-09-23 19:00:00', 'Asia/Yerevan'));

        $product = Product::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $this->category->id,
            'name' => 'Chicken Wrap',
            'price' => 2500,
            'discount_price' => 1900,
            'discount_days' => ['wednesday'],
            'discount_start_time' => '12:00',
            'discount_end_time' => '16:00',
            'is_discount_active' => true,
        ]);

        $response = $this->postJson(route('client.order.submit', ['vendor_slug' => $this->vendor->slug]), [
            'location_id' => $this->location->id,
            'type' => 'dine_in',
            'table_number' => 'Table 4',
            'customer_name' => 'Anahit',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $order = Order::where('vendor_id', $this->vendor->id)->latest()->first();
        $this->assertNotNull($order);
        // Regular price 2500 * 2 = 5000 AMD
        $this->assertEquals(5000, $order->total_amount);

        $orderItem = $order->items->first();
        $this->assertEquals(2500, $orderItem->unit_price);
        $this->assertEquals(5000, $orderItem->subtotal);

        Carbon::setTestNow();
    }
}
