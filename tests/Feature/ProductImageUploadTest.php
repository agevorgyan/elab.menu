<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageUploadTest extends TestCase
{
    use RefreshDatabase;

    protected Vendor $vendor;

    protected User $user;

    protected Category $category;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->vendor = Vendor::create([
            'name' => 'Upload Test Cafe',
            'slug' => 'upload-test-cafe',
            'email' => 'upload@test.com',
            'password' => bcrypt('password'),
        ]);

        $this->user = User::create([
            'name' => 'Upload Owner',
            'email' => 'upload@test.com',
            'password' => bcrypt('password'),
            'role' => 'vendor_owner',
            'vendor_id' => $this->vendor->id,
        ]);

        $this->category = Category::create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Main Dishes',
            'sort_order' => 1,
        ]);

        $this->product = Product::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $this->category->id,
            'name' => 'Delicious Pasta',
            'price' => 3500,
            'image' => Product::DEFAULT_IMAGE,
        ]);
    }

    public function test_vendor_can_upload_product_image_successfully(): void
    {
        $file = UploadedFile::fake()->image('fresh_pasta.jpg', 600, 600);

        $response = $this->actingAs($this->user)
            ->post("/admin/menu/products/{$this->product->id}", [
                'category_id' => $this->category->id,
                'name' => 'Delicious Pasta Updated',
                'price' => 3800,
                'image_file' => $file,
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->product->refresh();
        $this->assertEquals('Delicious Pasta Updated', $this->product->name);
        $this->assertEquals(3800, (int) $this->product->price);

        // Check image was stored in vendor isolated namespace
        $rawImage = $this->product->getRawOriginal('image');
        $this->assertStringStartsWith("/storage/vendors/{$this->vendor->uuid}/products/", $rawImage);
        $storedPath = str_replace('/storage/', '', $rawImage);
        Storage::disk('public')->assertExists($storedPath);
    }

    public function test_vendor_can_update_product_without_changing_image(): void
    {
        $response = $this->actingAs($this->user)
            ->post("/admin/menu/products/{$this->product->id}", [
                'category_id' => $this->category->id,
                'name' => 'Pasta Price Change',
                'price' => 4000,
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->product->refresh();
        $this->assertEquals(4000, (int) $this->product->price);
        $this->assertEquals(Product::DEFAULT_IMAGE, $this->product->image);
    }

    public function test_old_uploaded_image_is_purged_when_new_image_uploaded(): void
    {
        // First upload
        $firstFile = UploadedFile::fake()->image('first.jpg');
        $this->actingAs($this->user)
            ->post("/admin/menu/products/{$this->product->id}", [
                'category_id' => $this->category->id,
                'name' => 'Pasta v1',
                'price' => 3500,
                'image_file' => $firstFile,
            ]);

        $this->product->refresh();
        $firstRaw = $this->product->getRawOriginal('image');
        $firstPath = str_replace('/storage/', '', $firstRaw);
        Storage::disk('public')->assertExists($firstPath);

        // Second upload
        $secondFile = UploadedFile::fake()->image('second.png');
        $this->actingAs($this->user)
            ->post("/admin/menu/products/{$this->product->id}", [
                'category_id' => $this->category->id,
                'name' => 'Pasta v2',
                'price' => 3600,
                'image_file' => $secondFile,
            ]);

        $this->product->refresh();
        $secondRaw = $this->product->getRawOriginal('image');
        $secondPath = str_replace('/storage/', '', $secondRaw);

        // Old file deleted, new file exists
        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($secondPath);
    }
}
