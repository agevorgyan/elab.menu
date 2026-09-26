<?php

namespace Tests\Feature;

use App\Http\Controllers\MenuBuilderController;
use App\Http\Controllers\OrderController;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Category;
use App\Models\Location;
use App\Models\Order;
use App\Models\Product;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class IdorProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected Vendor $vendor1;

    protected User $user1;

    protected Vendor $vendor2;

    protected User $user2;

    protected Location $loc1;

    protected Location $loc2;

    protected Category $cat1;

    protected Category $cat2;

    protected Product $prod1;

    protected Product $prod2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SubscriptionPlanSeeder::class);

        $businessPlan = SubscriptionPlan::where('slug', 'business')->first();

        $this->vendor1 = Vendor::create([
            'name' => 'Vendor One',
            'slug' => 'vendor-one',
            'email' => 'v1@test.com',
            'password' => bcrypt('password'),
            'subscription_plan_id' => $businessPlan?->id,
            'subscription_plan' => 'business',
            'subscription_status' => 'active',
            'subscription_expires_at' => now()->addYear(),
            'is_active' => true,
        ]);

        $this->loc1 = Location::create([
            'vendor_id' => $this->vendor1->id,
            'name' => 'Branch 1',
            'slug' => 'branch-1',
            'table_count' => 10,
            'is_active' => true,
        ]);

        $this->user1 = User::create([
            'vendor_id' => $this->vendor1->id,
            'location_id' => $this->loc1->id,
            'name' => 'Owner One',
            'email' => 'owner1@test.com',
            'password' => bcrypt('password'),
            'role' => 'vendor_owner',
            'email_verified_at' => now(),
        ]);

        $this->vendor2 = Vendor::create([
            'name' => 'Vendor Two',
            'slug' => 'vendor-two',
            'email' => 'v2@test.com',
            'password' => bcrypt('password'),
            'subscription_plan_id' => $businessPlan?->id,
            'subscription_plan' => 'business',
            'subscription_status' => 'active',
            'subscription_expires_at' => now()->addYear(),
            'is_active' => true,
        ]);

        $this->loc2 = Location::create([
            'vendor_id' => $this->vendor2->id,
            'name' => 'Branch 2',
            'slug' => 'branch-2',
            'table_count' => 10,
            'is_active' => true,
        ]);

        $this->user2 = User::create([
            'vendor_id' => $this->vendor2->id,
            'location_id' => $this->loc2->id,
            'name' => 'Owner Two',
            'email' => 'owner2@test.com',
            'password' => bcrypt('password'),
            'role' => 'vendor_owner',
            'email_verified_at' => now(),
        ]);

        $this->cat1 = Category::create([
            'vendor_id' => $this->vendor1->id,
            'name' => 'Starters V1',
            'sort_order' => 1,
        ]);

        $this->cat2 = Category::create([
            'vendor_id' => $this->vendor2->id,
            'name' => 'Starters V2',
            'sort_order' => 1,
        ]);

        $this->prod1 = Product::create([
            'vendor_id' => $this->vendor1->id,
            'category_id' => $this->cat1->id,
            'name' => 'Dish V1',
            'price' => 1500,
        ]);

        $this->prod2 = Product::create([
            'vendor_id' => $this->vendor2->id,
            'category_id' => $this->cat2->id,
            'name' => 'Dish V2',
            'price' => 2500,
        ]);
    }

    public function test_vendor_cannot_update_or_delete_category_of_another_vendor(): void
    {
        // Vendor 1 tries to update Vendor 2's category
        $response = $this->actingAs($this->user1)->post(route('admin.menu.categories.update', $this->cat2->id), [
            'name' => 'Hacked Category',
        ]);
        $this->assertTrue(in_array($response->status(), [403, 404]), "Expected 403 or 404, got {$response->status()}");
        $this->assertEquals('Starters V2', $this->cat2->fresh()->name);

        // Vendor 1 tries to delete Vendor 2's category
        $deleteResponse = $this->actingAs($this->user1)->delete(route('admin.menu.categories.destroy', $this->cat2->id));
        $this->assertTrue(in_array($deleteResponse->status(), [403, 404]), "Expected 403 or 404, got {$deleteResponse->status()}");
        $this->assertDatabaseHas('categories', ['id' => $this->cat2->id]);
    }

    public function test_vendor_cannot_update_or_delete_dish_of_another_vendor(): void
    {
        // Vendor 1 tries to update Vendor 2's dish
        $response = $this->actingAs($this->user1)->post(route('admin.menu.products.update', $this->prod2->id), [
            'category_id' => $this->cat1->id,
            'name' => 'Hacked Dish',
            'price' => 999,
        ]);
        $this->assertTrue(in_array($response->status(), [403, 404]), "Expected 403 or 404, got {$response->status()}");
        $this->assertEquals('Dish V2', $this->prod2->fresh()->name);

        // Vendor 1 tries to delete Vendor 2's dish
        $deleteResponse = $this->actingAs($this->user1)->delete(route('admin.menu.products.destroy', $this->prod2->id));
        $this->assertTrue(in_array($deleteResponse->status(), [403, 404]), "Expected 403 or 404, got {$deleteResponse->status()}");
        $this->assertDatabaseHas('products', ['id' => $this->prod2->id]);
    }

    public function test_vendor_cannot_assign_dish_to_category_of_another_vendor(): void
    {
        // Vendor 1 tries to create a dish under Vendor 2's category
        $response = $this->actingAs($this->user1)->post(route('admin.menu.products.store'), [
            'category_id' => $this->cat2->id, // belongs to Vendor 2
            'name' => 'Sneaky Dish',
            'price' => 1200,
        ]);

        $this->assertTrue(in_array($response->status(), [403, 404]), "Expected 403 or 404, got {$response->status()}");
        $this->assertDatabaseMissing('products', ['name' => 'Sneaky Dish']);
    }

    public function test_vendor_cannot_update_order_status_of_another_vendor(): void
    {
        $order2 = Order::create([
            'vendor_id' => $this->vendor2->id,
            'location_id' => $this->loc2->id,
            'order_number' => 'ORD-V2-001',
            'table_number' => 'Table 1',
            'type' => 'dine_in',
            'total_amount' => 5000,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user1)->post(route('admin.orders.status', $order2->id), [
            'status' => 'ready',
        ]);

        $this->assertTrue(in_array($response->status(), [403, 404]), "Expected 403 or 404, got {$response->status()}");
        $this->assertEquals('pending', $order2->fresh()->status);
    }

    public function test_vendor_cannot_assign_team_member_to_location_of_another_vendor(): void
    {
        $response = $this->actingAs($this->user1)->post(route('admin.team.store'), [
            'name' => 'New Staff',
            'email' => 'staff@test.com',
            'role' => 'staff',
            'location_id' => $this->loc2->id, // belongs to Vendor 2
            'password' => 'secret123',
        ]);

        $this->assertEquals(403, $response->status());
        $this->assertDatabaseMissing('users', ['email' => 'staff@test.com']);
    }
}

