<?php

namespace Tests\Feature;

use App\Models\Language;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LanguageManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;

    protected User $vendorUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create([
            'role' => 'superadmin',
        ]);

        $vendor = Vendor::create([
            'name' => 'Test Vendor',
            'slug' => 'test-vendor-'.uniqid(),
        ]);

        $this->vendorUser = User::factory()->create([
            'role' => 'vendor_owner',
            'vendor_id' => $vendor->id,
        ]);
    }

    public function test_superadmin_can_view_languages_index(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('superadmin.languages.index'));

        $response->assertStatus(200);
        $response->assertSee('Platform Language Management');
    }

    public function test_non_superadmin_cannot_access_languages_index(): void
    {
        $response = $this->actingAs($this->vendorUser)->get(route('superadmin.languages.index'));

        $response->assertStatus(403);
    }

    public function test_superadmin_can_register_new_language(): void
    {
        $uniqueCode = 't'.substr(uniqid(), -3);

        $response = $this->actingAs($this->superAdmin)->post(route('superadmin.languages.store'), [
            'code' => $uniqueCode,
            'name' => 'Testish Language',
            'native_name' => 'Testish',
            'flag' => '🏳️',
            'direction' => 'ltr',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('superadmin.languages.index'));
        $this->assertDatabaseHas('languages', [
            'code' => $uniqueCode,
            'name' => 'Testish Language',
            'is_active' => true,
        ]);
    }

    public function test_cannot_deactivate_system_default_language(): void
    {
        $defaultLang = Language::where('is_default', true)->first();
        if (! $defaultLang) {
            $defaultLang = Language::create([
                'code' => 'def',
                'name' => 'Default Lang',
                'native_name' => 'Default',
                'is_default' => true,
                'is_active' => true,
            ]);
        }

        $response = $this->actingAs($this->superAdmin)->post(route('superadmin.languages.toggle', $defaultLang));

        $response->assertRedirect(route('superadmin.languages.index'));
        $response->assertSessionHas('error');
        $this->assertTrue($defaultLang->fresh()->is_active);
    }

    public function test_superadmin_can_set_new_default_language(): void
    {
        $lang = Language::where('is_default', false)->first();
        if (! $lang) {
            $lang = Language::create([
                'code' => 'newdef',
                'name' => 'New Default',
                'native_name' => 'New Default',
                'is_default' => false,
                'is_active' => true,
            ]);
        }

        $response = $this->actingAs($this->superAdmin)->post(route('superadmin.languages.default', $lang));

        $response->assertRedirect(route('superadmin.languages.index'));
        $this->assertTrue($lang->fresh()->is_default);
        $this->assertTrue($lang->fresh()->is_active);
    }
}
