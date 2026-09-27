<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchAndScheduleAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private Vendor $vendor;

    private User $user;

    private Location $locationA;

    private Location $locationB;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendor = Vendor::create([
            'name' => 'Multi-Branch Bistro',
            'slug' => 'multi-branch-bistro',
            'email' => 'bistro@example.com',
            'password' => bcrypt('secret123'),
            'is_active' => true,
            'subscription_status' => 'active',
            'subscription_expires_at' => now()->addDays(30),
        ]);

        $this->user = User::create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'password' => bcrypt('password'),
            'role' => 'vendor_owner',
        ]);

        $this->locationA = Location::create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Kentron Branch',
            'slug' => 'kentron-branch',
            'address' => 'Amiryan 1',
        ]);

        $this->locationB = Location::create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Arabkir Branch',
            'slug' => 'arabkir-branch',
            'address' => 'Komitas 15',
        ]);

        $this->category = Category::create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Main Courses',
        ]);
    }

    public function test_stock_toggle_in_one_branch_does_not_affect_other_branch(): void
    {
        $product = Product::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $this->category->id,
            'name' => 'Club Sandwich',
            'price' => 2500,
            'is_available' => true,
        ]);

        // Initially available in both branches
        $this->assertTrue($product->isAvailableAtLocation($this->locationA->id));
        $this->assertTrue($product->isAvailableAtLocation($this->locationB->id));

        // Toggle stock status in Location A to out of stock
        $response = $this->actingAs($this->user)
            ->post(route('admin.menu.products.toggle', $product), [
                'location_id' => $this->locationA->id,
            ]);

        $response->assertRedirect();

        $product->refresh();

        // Location A must now be OUT OF STOCK
        $this->assertFalse($product->isAvailableAtLocation($this->locationA->id));

        // Location B must still be IN STOCK (not affected!)
        $this->assertTrue($product->isAvailableAtLocation($this->locationB->id));

        // Attempting to order at Location A should fail
        $orderResA = $this->postJson(route('client.order.submit', ['vendor_slug' => $this->vendor->slug]), [
            'location_id' => $this->locationA->id,
            'table_number' => '5',
            'type' => 'dine_in',
            'customer_name' => 'Anna',
            'customer_phone' => '091223344',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ]);
        $orderResA->assertStatus(422);
        $orderResA->assertJsonValidationErrors(['items']);

        // Ordering at Location B should succeed
        $orderResB = $this->postJson(route('client.order.submit', ['vendor_slug' => $this->vendor->slug]), [
            'location_id' => $this->locationB->id,
            'table_number' => '10',
            'type' => 'dine_in',
            'customer_name' => 'Anna',
            'customer_phone' => '091223344',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ]);
        $orderResB->assertStatus(200);
        $this->assertTrue($orderResB->json('success'));
    }

    public function test_time_based_availability_schedule_enforcement(): void
    {
        $product = Product::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $this->category->id,
            'name' => 'Business Lunch',
            'price' => 1800,
            'is_available' => true,
            'available_start_time' => '11:00',
            'available_end_time' => '14:00',
            'available_days' => ['mon', 'tue', 'wed', 'thu', 'fri'],
        ]);

        // Case 1: Monday at 12:30 (inside lunch window)
        Carbon::setTestNow(Carbon::parse('2026-09-28 12:30:00', 'Asia/Yerevan')); // Monday
        $this->assertTrue($product->isTimeAvailable());

        $validRes = $this->postJson(route('client.order.submit', ['vendor_slug' => $this->vendor->slug]), [
            'location_id' => $this->locationA->id,
            'table_number' => '1',
            'type' => 'dine_in',
            'customer_name' => 'Karen',
            'customer_phone' => '091223344',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ]);
        $validRes->assertStatus(200);
        $this->assertTrue($validRes->json('success'));

        // Case 2: Monday at 14:15 (past 14:00 lunch window)
        Carbon::setTestNow(Carbon::parse('2026-09-28 14:15:00', 'Asia/Yerevan')); // Monday afternoon
        $this->assertFalse($product->isTimeAvailable());

        $expiredRes = $this->postJson(route('client.order.submit', ['vendor_slug' => $this->vendor->slug]), [
            'location_id' => $this->locationA->id,
            'table_number' => '1',
            'type' => 'dine_in',
            'customer_name' => 'Karen',
            'customer_phone' => '091223344',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ]);
        $expiredRes->assertStatus(422);
        $expiredRes->assertJsonValidationErrors(['items']);

        // Case 3: Sunday at 12:30 (excluded day)
        Carbon::setTestNow(Carbon::parse('2026-09-27 12:30:00', 'Asia/Yerevan')); // Sunday
        $this->assertFalse($product->isTimeAvailable());

        Carbon::setTestNow(); // reset
    }

    public function test_order_channel_availability_enforcement(): void
    {
        // Dish restricted to Dine-in ONLY
        $dineInProduct = Product::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $this->category->id,
            'name' => 'Hot Sizzling Fajita',
            'price' => 4500,
            'available_for_dine_in' => true,
            'available_for_takeaway' => false,
            'available_for_delivery' => false,
        ]);

        // Attempt delivery order with dine-in only dish -> fails
        $deliveryFail = $this->postJson(route('client.order.submit', ['vendor_slug' => $this->vendor->slug]), [
            'location_id' => $this->locationA->id,
            'type' => 'delivery',
            'delivery_address' => 'Baghramyan 24, apt 12',
            'customer_name' => 'Sona',
            'customer_phone' => '091223344',
            'items' => [
                ['product_id' => $dineInProduct->id, 'quantity' => 1],
            ],
        ]);
        $deliveryFail->assertStatus(422);
        $deliveryFail->assertJsonValidationErrors(['items']);

        // Dine-in order with dine-in only dish -> succeeds
        $dineInSuccess = $this->postJson(route('client.order.submit', ['vendor_slug' => $this->vendor->slug]), [
            'location_id' => $this->locationA->id,
            'table_number' => '7',
            'type' => 'dine_in',
            'customer_name' => 'Sona',
            'customer_phone' => '091223344',
            'items' => [
                ['product_id' => $dineInProduct->id, 'quantity' => 1],
            ],
        ]);
        $dineInSuccess->assertStatus(200);

        // Dish restricted to Delivery ONLY
        $deliveryProduct = Product::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $this->category->id,
            'name' => 'Family Delivery Combo',
            'price' => 9000,
            'available_for_dine_in' => false,
            'available_for_takeaway' => true,
            'available_for_delivery' => true,
        ]);

        // Dine-in order with delivery combo -> fails
        $dineInComboFail = $this->postJson(route('client.order.submit', ['vendor_slug' => $this->vendor->slug]), [
            'location_id' => $this->locationA->id,
            'table_number' => '7',
            'type' => 'dine_in',
            'customer_name' => 'Sona',
            'customer_phone' => '091223344',
            'items' => [
                ['product_id' => $deliveryProduct->id, 'quantity' => 1],
            ],
        ]);
        $dineInComboFail->assertStatus(422);
        $dineInComboFail->assertJsonValidationErrors(['items']);
    }

    public function test_product_creation_and_update_with_branch_and_schedule_settings(): void
    {
        // Vendor creates a product assigned only to Location A
        $createRes = $this->actingAs($this->user)->post(route('admin.menu.products.store'), [
            'category_id' => $this->category->id,
            'name' => 'Branch Specific Burger',
            'price' => 3000,
            'is_available' => true,
            'available_for_dine_in' => true,
            'available_for_takeaway' => true,
            'available_for_delivery' => false,
            'available_start_time' => '12:00',
            'available_end_time' => '23:00',
            'available_days' => ['mon', 'tue', 'wed', 'thu', 'fri', 'sat'],
            'locations' => [$this->locationA->id], // only Location A
        ]);

        $createRes->assertRedirect();

        $product = Product::where('name', 'Branch Specific Burger')->first();
        $this->assertNotNull($product);
        $this->assertEquals('12:00', $product->available_start_time);
        $this->assertEquals('23:00', $product->available_end_time);
        $this->assertFalse($product->available_for_delivery);
        $this->assertTrue($product->available_for_dine_in);

        // Location A is available, Location B is not available
        $this->assertTrue($product->isAvailableAtLocation($this->locationA->id));
        $this->assertFalse($product->isAvailableAtLocation($this->locationB->id));

        // Now update product to make it available in both locations
        $updateRes = $this->actingAs($this->user)->post(route('admin.menu.products.update', $product), [
            'category_id' => $this->category->id,
            'name' => 'Branch Specific Burger (Updated)',
            'price' => 3200,
            'is_available' => true,
            'available_for_dine_in' => true,
            'available_for_takeaway' => true,
            'available_for_delivery' => true,
            'locations' => [$this->locationA->id, $this->locationB->id],
        ]);

        $updateRes->assertRedirect();

        $product->refresh();
        $this->assertEquals(3200, (int) $product->price);
        $this->assertTrue($product->isAvailableAtLocation($this->locationA->id));
        $this->assertTrue($product->isAvailableAtLocation($this->locationB->id));
    }
}
