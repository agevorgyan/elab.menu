<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Vendor;
use App\Models\MenuTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VendorSelfRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_can_self_register_with_all_legal_fields(): void
    {
        $this->seed();

        $response = $this->post('/register', [
            'type' => 'hotel',
            'name' => 'Verona Boutique Hotel',
            'expected_locations_count' => 3,
            'operating_address' => 'Baghramyan Ave 24, Yerevan',
            'legal_name' => '«Վերոնա Հոտել» ՍՊԸ',
            'legal_address' => 'ք․ Երևան, Բաղրամյան 24',
            'tax_id' => '02698741',
            'director_name' => 'Գևորգ Գևորգյան',
            'contact_person_name' => 'Մարիամ Ավագյան',
            'phone' => '+37491998877',
            'email' => 'info@veronahotel.am',
            'subscription_plan' => 'enterprise',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertRedirect('/email/verify');
        $this->assertDatabaseHas('vendors', [
            'name' => 'Verona Boutique Hotel',
            'legal_name' => '«Վերոնա Հոտել» ՍՊԸ',
            'tax_id' => '02698741',
            'director_name' => 'Գևորգ Գևորգյան',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'info@veronahotel.am',
            'role' => 'vendor_owner',
        ]);
    }
}
