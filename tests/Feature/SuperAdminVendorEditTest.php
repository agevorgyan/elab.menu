<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\MenuTemplate;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperAdminVendorEditTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;

    private Vendor $vendor;

    private Location $location;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superadmin = User::factory()->create([
            'email' => 'superadmin@qrmenu.local',
            'role' => 'superadmin',
            'password' => Hash::make('secret123'),
        ]);

        $template = MenuTemplate::create([
            'name' => 'Modern Minimal',
            'slug' => 'modern-minimal',
            'is_active' => true,
        ]);

        $plan = SubscriptionPlan::create([
            'name' => 'Pro Plan',
            'slug' => 'pro',
            'price' => 15000,
            'currency' => 'AMD',
            'billing_interval' => 'monthly',
            'duration_days' => 30,
            'trial_days' => 14,
            'is_active' => true,
        ]);

        $this->vendor = Vendor::create([
            'name' => 'Bistro Test',
            'slug' => 'bistro-test',
            'type' => 'restaurant',
            'email' => 'info@bistrotest.am',
            'phone' => '+37499111222',
            'currency' => 'AMD',
            'menu_template_id' => $template->id,
            'subscription_plan' => 'pro',
            'subscription_plan_id' => $plan->id,
            'is_active' => true,
        ]);

        $this->location = Location::create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Main Location',
            'slug' => 'main-loc',
            'table_count' => 20,
            'address' => 'Yerevan Center',
            'is_active' => true,
        ]);

        $this->owner = User::factory()->create([
            'name' => 'Vendor Owner',
            'email' => 'owner@bistrotest.am',
            'role' => 'vendor_owner',
            'vendor_id' => $this->vendor->id,
            'location_id' => $this->location->id,
            'password' => Hash::make('owner123'),
        ]);
    }

    public function test_superadmin_can_view_vendor_edit_page(): void
    {
        $response = $this->actingAs($this->superadmin)
            ->get(route('superadmin.vendors.edit', $this->vendor->id));

        $response->assertStatus(200);
        $response->assertSee('Bistro Test');
        $response->assertSee('bistro-test');
        $response->assertSee('Main Location');
        $response->assertSee('owner@bistrotest.am');
    }

    public function test_superadmin_can_update_vendor_links_and_details(): void
    {
        $response = $this->actingAs($this->superadmin)
            ->put(route('superadmin.vendors.update', $this->vendor->id), [
                'name' => 'Bistro Test Deluxe',
                'slug' => 'bistro-deluxe',
                'type' => 'cafe',
                'custom_domain' => 'https://menu.bistrodeluxe.am/',
                'currency' => 'USD',
                'menu_template_id' => $this->vendor->menu_template_id,
                'subscription_plan_id' => $this->vendor->subscription_plan_id,
                'phone' => '+37410999888',
                'email' => 'contact@bistrodeluxe.am',
                'delivery_enabled' => '1',
            ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('vendors', [
            'id' => $this->vendor->id,
            'name' => 'Bistro Test Deluxe',
            'slug' => 'bistro-deluxe',
            'custom_domain' => 'menu.bistrodeluxe.am',
            'currency' => 'USD',
            'delivery_enabled' => 1,
        ]);
    }

    public function test_superadmin_can_update_location_table_count(): void
    {
        $response = $this->actingAs($this->superadmin)
            ->post(route('superadmin.vendors.locations.tables', [$this->vendor->id, $this->location->id]), [
                'table_count' => 45,
            ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('locations', [
            'id' => $this->location->id,
            'table_count' => 45,
        ]);
    }

    public function test_superadmin_can_add_update_and_delete_branch(): void
    {
        // Add branch
        $storeResponse = $this->actingAs($this->superadmin)
            ->post(route('superadmin.vendors.locations.store', $this->vendor->id), [
                'name' => 'North Avenue Branch',
                'slug' => 'north-avenue',
                'table_count' => 30,
                'address' => 'North Ave 10',
                'phone' => '+37411223344',
                'allow_dine_in_orders' => '1',
            ]);

        $storeResponse->assertSessionHas('success');
        $newBranch = Location::where('slug', 'north-avenue')->first();
        $this->assertNotNull($newBranch);
        $this->assertEquals(30, $newBranch->table_count);

        // Update branch
        $updateResponse = $this->actingAs($this->superadmin)
            ->put(route('superadmin.vendors.locations.update', [$this->vendor->id, $newBranch->id]), [
                'name' => 'North Avenue VIP',
                'slug' => 'north-avenue-vip',
                'table_count' => 35,
                'address' => 'North Ave 12',
                'is_active' => '1',
            ]);

        $updateResponse->assertSessionHas('success');
        $this->assertDatabaseHas('locations', [
            'id' => $newBranch->id,
            'name' => 'North Avenue VIP',
            'table_count' => 35,
        ]);

        // Delete branch
        $deleteResponse = $this->actingAs($this->superadmin)
            ->delete(route('superadmin.vendors.locations.destroy', [$this->vendor->id, $newBranch->id]));

        $deleteResponse->assertSessionHas('success');
        $this->assertDatabaseMissing('locations', ['id' => $newBranch->id]);
    }

    public function test_superadmin_cannot_delete_last_remaining_branch(): void
    {
        $response = $this->actingAs($this->superadmin)
            ->delete(route('superadmin.vendors.locations.destroy', [$this->vendor->id, $this->location->id]));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('locations', ['id' => $this->location->id]);
    }

    public function test_superadmin_can_add_update_and_delete_vendor_user(): void
    {
        // Add user
        $storeResponse = $this->actingAs($this->superadmin)
            ->post(route('superadmin.vendors.users.store', $this->vendor->id), [
                'name' => 'Armen Waiter',
                'email' => 'armen@bistrotest.am',
                'role' => 'waiter',
                'location_id' => $this->location->id,
                'password' => 'password123',
            ]);

        $storeResponse->assertSessionHas('success');
        $newUser = User::where('email', 'armen@bistrotest.am')->first();
        $this->assertNotNull($newUser);
        $this->assertEquals('waiter', $newUser->role);

        // Update user (change role and password)
        $updateResponse = $this->actingAs($this->superadmin)
            ->put(route('superadmin.vendors.users.update', [$this->vendor->id, $newUser->id]), [
                'name' => 'Armen Senior Waiter',
                'email' => 'armen.senior@bistrotest.am',
                'role' => 'branch_manager',
                'location_id' => $this->location->id,
                'password' => 'newpassword789',
            ]);

        $updateResponse->assertSessionHas('success');
        $newUser->refresh();
        $this->assertEquals('armen.senior@bistrotest.am', $newUser->email);
        $this->assertEquals('branch_manager', $newUser->role);
        $this->assertTrue(Hash::check('newpassword789', $newUser->password));

        // Delete user
        $deleteResponse = $this->actingAs($this->superadmin)
            ->delete(route('superadmin.vendors.users.destroy', [$this->vendor->id, $newUser->id]));

        $deleteResponse->assertSessionHas('success');
        $this->assertDatabaseMissing('users', ['id' => $newUser->id]);
    }

    public function test_superadmin_can_update_own_security_email_and_password(): void
    {
        // Change Email with correct password
        $emailResponse = $this->actingAs($this->superadmin)
            ->post(route('superadmin.settings.security'), [
                'action_type' => 'email',
                'current_password' => 'secret123',
                'email' => 'new-superadmin@qrmenu.local',
            ]);

        $emailResponse->assertSessionHas('success');
        $this->superadmin->refresh();
        $this->assertEquals('new-superadmin@qrmenu.local', $this->superadmin->email);

        // Change Email fails with wrong password
        $failEmailResponse = $this->actingAs($this->superadmin)
            ->post(route('superadmin.settings.security'), [
                'action_type' => 'email',
                'current_password' => 'wrong-pass',
                'email' => 'fail@qrmenu.local',
            ]);

        $failEmailResponse->assertSessionHasErrors('current_password');

        // Change Password with correct password and confirmation
        $passResponse = $this->actingAs($this->superadmin)
            ->post(route('superadmin.settings.security'), [
                'action_type' => 'password',
                'current_password' => 'secret123',
                'password' => 'brand-new-secret',
                'password_confirmation' => 'brand-new-secret',
            ]);

        $passResponse->assertSessionHas('success');
        $this->superadmin->refresh();
        $this->assertTrue(Hash::check('brand-new-secret', $this->superadmin->password));
    }

    public function test_non_superadmin_cannot_access_vendor_edit(): void
    {
        $response = $this->actingAs($this->owner)
            ->get(route('superadmin.vendors.edit', $this->vendor->id));

        $response->assertStatus(403);
    }
}
