<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Location;
use App\Models\Order;
use App\Models\Product;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormRequestValidationTest extends TestCase
{
    use RefreshDatabase;

    protected Vendor $vendor1;

    protected User $user1;

    protected Location $location1;

    protected Category $category1;

    protected Product $product1;

    protected Vendor $vendor2;

    protected User $user2;

    protected Location $location2;

    protected Category $category2;

    protected Product $product2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SubscriptionPlanSeeder::class);

        $plan = SubscriptionPlan::where('slug', 'business')->first();

        // Vendor 1
        $this->vendor1 = Vendor::create([
            'name' => 'Restaurant Alpha',
            'slug' => 'restaurant-alpha',
            'email' => 'alpha@test.com',
            'password' => bcrypt('password'),
            'subscription_plan_id' => $plan?->id,
            'subscription_plan' => 'business',
            'subscription_status' => 'active',
            'subscription_expires_at' => now()->addYear(),
            'is_active' => true,
        ]);

        $this->location1 = Location::create([
            'vendor_id' => $this->vendor1->id,
            'name' => 'Alpha Center',
            'slug' => 'alpha-center',
            'table_count' => 10,
            'is_active' => true,
        ]);

        $this->user1 = User::create([
            'vendor_id' => $this->vendor1->id,
            'location_id' => $this->location1->id,
            'name' => 'Alpha Owner',
            'email' => 'alpha_owner@test.com',
            'password' => bcrypt('password'),
            'role' => 'vendor_owner',
            'email_verified_at' => now(),
        ]);

        $this->category1 = Category::create([
            'vendor_id' => $this->vendor1->id,
            'name' => 'Alpha Mains',
            'sort_order' => 1,
        ]);

        $this->product1 = Product::create([
            'vendor_id' => $this->vendor1->id,
            'category_id' => $this->category1->id,
            'name' => 'Alpha Burger',
            'price' => 3000,
        ]);

        // Vendor 2
        $this->vendor2 = Vendor::create([
            'name' => 'Restaurant Beta',
            'slug' => 'restaurant-beta',
            'email' => 'beta@test.com',
            'password' => bcrypt('password'),
            'subscription_plan_id' => $plan?->id,
            'subscription_plan' => 'business',
            'subscription_status' => 'active',
            'subscription_expires_at' => now()->addYear(),
            'is_active' => true,
        ]);

        $this->location2 = Location::create([
            'vendor_id' => $this->vendor2->id,
            'name' => 'Beta Branch',
            'slug' => 'beta-branch',
            'table_count' => 10,
            'is_active' => true,
        ]);

        $this->user2 = User::create([
            'vendor_id' => $this->vendor2->id,
            'location_id' => $this->location2->id,
            'name' => 'Beta Owner',
            'email' => 'beta_owner@test.com',
            'password' => bcrypt('password'),
            'role' => 'vendor_owner',
            'email_verified_at' => now(),
        ]);

        $this->category2 = Category::create([
            'vendor_id' => $this->vendor2->id,
            'name' => 'Beta Pizzas',
            'sort_order' => 1,
        ]);

        $this->product2 = Product::create([
            'vendor_id' => $this->vendor2->id,
            'category_id' => $this->category2->id,
            'name' => 'Beta Pizza',
            'price' => 4500,
        ]);
    }

    public function test_submit_order_request_validates_required_fields(): void
    {
        $response = $this->postJson(route('client.order.submit', $this->vendor1->slug), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['location_id', 'type', 'items']);
    }

    public function test_submit_order_request_rejects_cross_tenant_location(): void
    {
        $response = $this->postJson(route('client.order.submit', $this->vendor1->slug), [
            'location_id' => $this->location2->id, // belongs to Beta!
            'type' => 'dine_in',
            'items' => [
                ['product_id' => $this->product1->id, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['location_id']);
    }

    public function test_submit_order_request_rejects_cross_tenant_items(): void
    {
        $response = $this->postJson(route('client.order.submit', $this->vendor1->slug), [
            'location_id' => $this->location1->id,
            'type' => 'dine_in',
            'items' => [
                ['product_id' => $this->product2->id, 'quantity' => 1], // belongs to Beta!
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items.0.product_id']);
    }

    public function test_store_product_request_validates_required_attributes(): void
    {
        $response = $this->actingAs($this->user1)->post(route('admin.menu.products.store'), []);

        $response->assertSessionHasErrors(['category_id', 'name', 'price']);
    }

    public function test_store_category_request_validates_required_name(): void
    {
        $response = $this->actingAs($this->user1)->post(route('admin.menu.categories.store'), []);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_update_order_status_request_validates_allowed_statuses(): void
    {
        $order = Order::create([
            'vendor_id' => $this->vendor1->id,
            'location_id' => $this->location1->id,
            'order_number' => 'ORD-100',
            'type' => 'dine_in',
            'total_amount' => 3000,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user1)->post(route('admin.orders.status', $order->id), [
            'status' => 'invalid_status_value',
        ]);

        $response->assertSessionHasErrors(['status']);
        $this->assertEquals('pending', $order->fresh()->status);
    }
}
