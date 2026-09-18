<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLocaleSwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_switch_admin_locale_to_english_and_russian(): void
    {
        $superadmin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@qrmenu.local',
            'password' => bcrypt('password'),
            'role' => 'superadmin',
        ]);

        // 1. Switch to English
        $response = $this->actingAs($superadmin)->get(route('lang.switch', 'en'));
        $response->assertRedirect();
        $this->assertEquals('en', session('app_locale'));

        $dashRes = $this->actingAs($superadmin)->get(route('superadmin.dashboard'));
        $dashRes->assertStatus(200);
        $dashRes->assertSee('Subscription Plans');

        // 2. Switch to Russian
        $ruResponse = $this->actingAs($superadmin)->get(route('lang.switch', 'ru'));
        $ruResponse->assertRedirect();
        $this->assertEquals('ru', session('app_locale'));

        $ruDashRes = $this->actingAs($superadmin)->get(route('superadmin.dashboard'));
        $ruDashRes->assertStatus(200);
        $ruDashRes->assertSee('Тарифные Планы');

        // 3. Switch to Armenian
        $hyResponse = $this->actingAs($superadmin)->get(route('lang.switch', 'hy'));
        $hyResponse->assertRedirect();
        $this->assertEquals('hy', session('app_locale'));
    }
}
