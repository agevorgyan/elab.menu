<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SuperAdminSettingsBrandingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_superadmin_can_view_settings_page_with_branding_and_seo_sections(): void
    {
        $superadmin = User::where('role', 'superadmin')->first();
        $this->actingAs($superadmin);

        $response = $this->get(route('superadmin.settings.index'));

        $response->assertStatus(200);
        $response->assertSee('Համակարգի Բրենդինգ');
        $response->assertSee('SEO Օպտիմիզացիա');
        $response->assertSee('name="site_name"', false);
        $response->assertSee('name="site_tagline"', false);
        $response->assertSee('name="logo_light_file"', false);
        $response->assertSee('name="logo_dark_file"', false);
        $response->assertSee('name="favicon_file"', false);
        $response->assertSee('name="seo_title"', false);
        $response->assertSee('name="seo_description"', false);
        $response->assertSee('name="og_image_file"', false);
    }

    public function test_superadmin_can_update_branding_text_and_seo_settings(): void
    {
        $superadmin = User::where('role', 'superadmin')->first();
        $this->actingAs($superadmin);

        $response = $this->post(route('superadmin.settings.update'), [
            'contact_phone' => '+37455112233',
            'contact_email' => 'admin@test.com',
            'trial_days' => 14,
            'site_name' => 'SmartMenu Platform',
            'site_tagline' => 'Best digital restaurant menus in Armenia',
            'seo_title' => 'SmartMenu — Armenian QR Menu',
            'seo_title_en' => 'SmartMenu — Best QR Restaurant System',
            'seo_description' => 'Create digital interactive QR menus with AI waiter',
            'seo_description_en' => 'Digital menu QR ordering platform',
            'seo_keywords' => 'qr menu, smart menu, armenia, restaurants',
            'footer_copyright' => '© 2026 SmartMenu LLC. All rights reserved.',
        ]);

        $response->assertSessionHas('success');

        $this->assertSame('SmartMenu Platform', SystemSetting::getSiteName());
        $this->assertSame('Best digital restaurant menus in Armenia', SystemSetting::getSiteTagline());
        $this->assertSame('SmartMenu — Armenian QR Menu', SystemSetting::getSeoTitle('hy'));
        $this->assertSame('SmartMenu — Best QR Restaurant System', SystemSetting::getSeoTitle('en'));
        $this->assertSame('Create digital interactive QR menus with AI waiter', SystemSetting::getSeoDescription('hy'));
        $this->assertSame('Digital menu QR ordering platform', SystemSetting::getSeoDescription('en'));
        $this->assertSame('qr menu, smart menu, armenia, restaurants', SystemSetting::getSeoKeywords());
        $this->assertSame('© 2026 SmartMenu LLC. All rights reserved.', SystemSetting::get('footer_copyright'));
    }

    public function test_superadmin_can_upload_branding_files_and_remove_them(): void
    {
        Storage::fake('public');

        $superadmin = User::where('role', 'superadmin')->first();
        $this->actingAs($superadmin);

        $lightLogo = UploadedFile::fake()->image('custom_logo_light.png', 300, 100);
        $darkLogo = UploadedFile::fake()->image('custom_logo_dark.png', 300, 100);
        $favicon = UploadedFile::fake()->image('custom_favicon.png', 64, 64);
        $ogImage = UploadedFile::fake()->image('custom_og.jpg', 1200, 630);

        $response = $this->post(route('superadmin.settings.update'), [
            'contact_phone' => '+37455112233',
            'contact_email' => 'admin@test.com',
            'trial_days' => 14,
            'site_name' => 'Custom Branding Test',
            'logo_light_file' => $lightLogo,
            'logo_dark_file' => $darkLogo,
            'favicon_file' => $favicon,
            'og_image_file' => $ogImage,
        ]);

        $response->assertSessionHas('success');

        $lightPath = SystemSetting::get('site_logo_light');
        $darkPath = SystemSetting::get('site_logo_dark');
        $favPath = SystemSetting::get('site_favicon');
        $ogPath = SystemSetting::get('seo_og_image');

        $this->assertNotNull($lightPath);
        $this->assertNotNull($darkPath);
        $this->assertNotNull($favPath);
        $this->assertNotNull($ogPath);

        // Verify storage files exist
        $lightDiskPath = str_replace('/storage/', '', $lightPath);
        $darkDiskPath = str_replace('/storage/', '', $darkPath);
        $favDiskPath = str_replace('/storage/', '', $favPath);
        $ogDiskPath = str_replace('/storage/', '', $ogPath);

        Storage::disk('public')->assertExists($lightDiskPath);
        Storage::disk('public')->assertExists($darkDiskPath);
        Storage::disk('public')->assertExists($favDiskPath);
        Storage::disk('public')->assertExists($ogDiskPath);

        $this->assertSame(asset(ltrim($lightPath, '/')), SystemSetting::getLogoLight());
        $this->assertSame(asset(ltrim($darkPath, '/')), SystemSetting::getLogoDark());
        $this->assertSame(asset(ltrim($favPath, '/')), SystemSetting::getFavicon());
        $this->assertSame(asset(ltrim($ogPath, '/')), SystemSetting::getOgImage());

        // Now test removing light logo
        $removeResponse = $this->post(route('superadmin.settings.update'), [
            'contact_phone' => '+37455112233',
            'contact_email' => 'admin@test.com',
            'trial_days' => 14,
            'remove_logo_light' => '1',
        ]);

        $removeResponse->assertSessionHas('success');
        Storage::disk('public')->assertMissing($lightDiskPath);
        $this->assertNull(SystemSetting::get('site_logo_light'));
        // Fallback to default
        $this->assertStringContainsString('branding/logo-light.png', SystemSetting::getLogoLight());
    }

    public function test_landing_page_renders_dynamic_branding_and_seo(): void
    {
        SystemSetting::set('site_name', 'GourmetQR System');
        SystemSetting::set('site_tagline', 'Modern Dining Experience');
        SystemSetting::set('seo_title', 'GourmetQR — Premium Restaurant Platform');
        SystemSetting::set('seo_description', 'High performance digital menu software');
        SystemSetting::set('footer_copyright', '© 2026 GourmetQR International');

        $response = $this->get(route('landing'));

        $response->assertStatus(200);
        $response->assertSee('GourmetQR — Premium Restaurant Platform');
        $response->assertSee('High performance digital menu software');
        $response->assertSee('© 2026 GourmetQR International');
    }

    public function test_non_superadmin_cannot_access_or_update_branding_settings(): void
    {
        $vendorUser = User::where('role', 'vendor_owner')->first();
        $this->actingAs($vendorUser);

        $response = $this->get(route('superadmin.settings.index'));
        $this->assertTrue(in_array($response->status(), [403, 302]));

        $updateResponse = $this->post(route('superadmin.settings.update'), [
            'site_name' => 'Hacked Name',
        ]);
        $this->assertTrue(in_array($updateResponse->status(), [403, 302]));
    }
}
