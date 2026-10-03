<?php

namespace Tests\Feature;

use App\Models\Language;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLocaleSwitchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure base languages exist in test environment
        Language::firstOrCreate(
            ['code' => 'en'],
            ['name' => 'English', 'native_name' => 'English', 'flag' => '🇬🇧', 'direction' => 'ltr', 'is_active' => true, 'is_default' => true, 'sort_order' => 1]
        );
        Language::firstOrCreate(
            ['code' => 'hy'],
            ['name' => 'Armenian', 'native_name' => 'Հայերեն', 'flag' => '🇦🇲', 'direction' => 'ltr', 'is_active' => true, 'is_default' => false, 'sort_order' => 2]
        );
        Language::firstOrCreate(
            ['code' => 'ru'],
            ['name' => 'Russian', 'native_name' => 'Русский', 'flag' => '🇷🇺', 'direction' => 'ltr', 'is_active' => true, 'is_default' => false, 'sort_order' => 3]
        );
    }

    public function test_default_system_locale_is_english(): void
    {
        $superadmin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@qrmenu.local',
            'password' => bcrypt('password'),
            'role' => 'superadmin',
        ]);

        // Default access to dashboard without session is English
        $dashRes = $this->actingAs($superadmin)->get(route('superadmin.dashboard'));
        $dashRes->assertStatus(200);
        $dashRes->assertSee('Subscription Plans');
        $this->assertEquals('en', app()->getLocale());
    }

    public function test_switching_to_valid_supported_locale_works(): void
    {
        $superadmin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@qrmenu.local',
            'password' => bcrypt('password'),
            'role' => 'superadmin',
        ]);

        $response = $this->actingAs($superadmin)->get(route('lang.switch', 'hy'));
        $response->assertRedirect();
        $this->assertEquals('hy', session('admin_locale'));

        $ruResponse = $this->actingAs($superadmin)->get(route('lang.switch', 'ru'));
        $ruResponse->assertRedirect();
        $this->assertEquals('ru', session('admin_locale'));
    }

    public function test_switching_to_unsupported_locale_is_rejected(): void
    {
        $superadmin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@qrmenu.local',
            'password' => bcrypt('password'),
            'role' => 'superadmin',
        ]);

        // Set to English
        $this->actingAs($superadmin)->get(route('lang.switch', 'en'));
        $this->assertEquals('en', session('admin_locale'));

        // Attempt invalid locale
        $invalidResponse = $this->actingAs($superadmin)->get(route('lang.switch', 'invalid_xx'));
        $invalidResponse->assertRedirect();
        $this->assertEquals('en', session('admin_locale'));
    }
}
