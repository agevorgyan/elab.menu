<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\Vendor;
use App\Services\CustomerSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_syncs_existing_customer_by_phone(): void
    {
        $vendor = Vendor::create([
            'name' => 'Burger Hub',
            'slug' => 'burger-hub',
            'email' => 'hub@burger.com',
            'password' => bcrypt('secret'),
        ]);

        $location = \App\Models\Location::create([
            'vendor_id' => $vendor->id,
            'name' => 'Main Branch',
            'slug' => 'main-branch',
        ]);

        $service = new CustomerSyncService();

        // Initial creation
        $customer1 = $service->syncCustomerFromOrder(
            vendor: $vendor,
            name: 'Samvel',
            phone: '099112233',
            email: 'samvel@old.com',
            marketingOptIn: false,
            locationId: $location->id
        );

        $this->assertNotNull($customer1);
        $this->assertEquals('Samvel', $customer1->name);
        $this->assertFalse($customer1->marketing_opt_in);

        // Subsequent order updates consent and details
        $customer2 = $service->syncCustomerFromOrder(
            vendor: $vendor,
            name: 'Samvel G.',
            phone: '099112233',
            email: 'samvel@new.com',
            marketingOptIn: true,
            locationId: $location->id
        );

        $this->assertEquals($customer1->id, $customer2->id);
        $this->assertEquals('Samvel G.', $customer2->name);
        $this->assertEquals('samvel@new.com', $customer2->email);
        $this->assertTrue($customer2->marketing_opt_in);
    }

    public function test_admin_crud_methods(): void
    {
        $vendor = Vendor::create([
            'name' => 'Cafe Bistro',
            'slug' => 'cafe-bistro',
            'email' => 'info@bistro.com',
            'password' => bcrypt('secret'),
        ]);

        $service = new CustomerSyncService();

        $customer = $service->createCustomer($vendor, [
            'name' => 'Anahit',
            'phone' => '077554433',
            'email' => 'anahit@example.com',
        ], true);

        $this->assertEquals('Anahit', $customer->name);
        $this->assertTrue($customer->marketing_opt_in);

        $service->updateCustomer($customer, [
            'name' => 'Anahit M.',
            'phone' => '077554433',
        ], false);

        $this->assertEquals('Anahit M.', $customer->fresh()->name);
        $this->assertFalse($customer->fresh()->marketing_opt_in);

        $service->deleteCustomer($customer);
        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }
}
