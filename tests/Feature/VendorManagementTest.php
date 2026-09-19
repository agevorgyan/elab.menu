<?php

namespace Tests\Feature;

use App\Models\MenuTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_create_vendor_without_phone(): void
    {
        $this->seed();

        $superadmin = User::where('role', 'superadmin')->first();
        $template = MenuTemplate::first();

        $response = $this->actingAs($superadmin)->post('/superadmin/vendors', [
            'name' => 'New Test Cafe',
            'type' => 'cafe',
            'email' => 'owner@newtestcafe.am',
            'subscription_plan' => 'pro',
            'menu_template_id' => $template->id,
            'owner_name' => 'Test Owner',
            'password' => 'password123',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('vendors', ['name' => 'New Test Cafe']);
        $this->assertDatabaseHas('users', ['email' => 'owner@newtestcafe.am']);
    }
}
