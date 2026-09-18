<?php

namespace App\Services;

use App\Models\Category;
use App\Models\LocationProductOverride;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Vendor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MenuManagementService
{
    /**
     * Create a new category for a vendor.
     */
    public function createCategory(Vendor $vendor, array $data): Category
    {
        $name = $data['name'];

        return Category::create([
            'vendor_id' => $vendor->id,
            'name' => $name,
            'name_translations' => [
                'hy' => $data['hy_name'] ?? $name,
                'en' => $name,
                'ru' => $data['ru_name'] ?? $name,
            ],
            'description' => $data['description'] ?? null,
            'sort_order' => (int) (Category::where('vendor_id', $vendor->id)->max('sort_order') ?? 0) + 1,
            'is_active' => true,
        ]);
    }

    /**
     * Update existing category.
     */
    public function updateCategory(Category $category, array $data): Category
    {
        $name = $data['name'];

        $category->update([
            'name' => $name,
            'name_translations' => [
                'hy' => $data['hy_name'] ?? $name,
                'en' => $name,
                'ru' => $data['ru_name'] ?? $name,
            ],
            'description' => $data['description'] ?? null,
        ]);

        return $category;
    }

    /**
     * Delete a category.
     */
    public function deleteCategory(Category $category): void
    {
        $category->delete();
    }

    /**
     * Create a new dish/product with translations, nutritional info, allergens and default variation.
     */
    public function createProduct(Vendor $vendor, array $data, ?UploadedFile $imageFile = null): Product
    {
        $imageUrl = $this->resolveProductImage(
            imageFile: $imageFile,
            fallbackUrl: $data['image'] ?? null,
            defaultUrl: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=600&q=80'
        );

        $name = $data['name'];
        $desc = $data['description'] ?? null;

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $data['category_id'],
            'name' => $name,
            'name_translations' => [
                'hy' => $data['hy_name'] ?? $name,
                'en' => $name,
                'ru' => $data['ru_name'] ?? $name,
            ],
            'description' => $desc,
            'description_translations' => [
                'hy' => $data['hy_description'] ?? $desc,
                'en' => $desc,
                'ru' => $data['ru_description'] ?? $desc,
            ],
            'price' => $data['price'],
            'image' => $imageUrl,
            'dietary_tags' => $data['dietary_tags'] ?? [],
            'calories' => $data['calories'] ?? null,
            'protein_g' => $data['protein_g'] ?? null,
            'carbs_g' => $data['carbs_g'] ?? null,
            'fat_g' => $data['fat_g'] ?? null,
            'preparation_time_min' => $data['preparation_time_min'] ?? null,
            'is_featured' => !empty($data['is_featured']),
            'is_available' => true,
            'sort_order' => (int) (Product::where('category_id', $data['category_id'])->max('sort_order') ?? 0) + 1,
        ]);

        if (!empty($data['allergens'])) {
            $product->allergens()->sync($data['allergens']);
        }

        // Initialize default standard portion variation
        ProductVariation::create([
            'product_id' => $product->id,
            'name' => 'Standard Portion',
            'price' => $data['price'],
            'is_default' => true,
        ]);

        return $product;
    }

    /**
     * Update an existing product, handling image replacement and allergen relationships.
     */
    public function updateProduct(Product $product, array $data, ?UploadedFile $imageFile = null): Product
    {
        $oldImage = $product->image;

        $imageUrl = $this->resolveProductImage(
            imageFile: $imageFile,
            fallbackUrl: $data['image'] ?? null,
            defaultUrl: $product->image ?? 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=600&q=80'
        );

        // If a new image was set and the old image was stored locally, purge the old file
        if ($oldImage && $imageUrl !== $oldImage && !str_starts_with($oldImage, 'http://') && !str_starts_with($oldImage, 'https://')) {
            $oldPath = ltrim(str_replace('/storage/', '', $oldImage), '/');
            if (!empty($oldPath) && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $name = $data['name'];
        $desc = $data['description'] ?? null;

        $updatePayload = [
            'category_id' => $data['category_id'],
            'name' => $name,
            'name_translations' => [
                'hy' => $data['hy_name'] ?? $name,
                'en' => $name,
                'ru' => $data['ru_name'] ?? $name,
            ],
            'description' => $desc,
            'description_translations' => [
                'hy' => $data['hy_description'] ?? $desc,
                'en' => $desc,
                'ru' => $data['ru_description'] ?? $desc,
            ],
            'price' => $data['price'],
            'image' => $imageUrl,
            'dietary_tags' => $data['dietary_tags'] ?? [],
            'calories' => $data['calories'] ?? null,
            'protein_g' => $data['protein_g'] ?? null,
            'carbs_g' => $data['carbs_g'] ?? null,
            'fat_g' => $data['fat_g'] ?? null,
            'preparation_time_min' => $data['preparation_time_min'] ?? null,
            'is_featured' => !empty($data['is_featured']),
        ];

        if (array_key_exists('is_available', $data)) {
            $updatePayload['is_available'] = (bool) $data['is_available'];
        }

        $product->update($updatePayload);

        if (!empty($data['allergens'])) {
            $product->allergens()->sync($data['allergens']);
        } else {
            $product->allergens()->detach();
        }

        return $product;
    }

    /**
     * Toggle product availability on/off.
     */
    public function toggleProductAvailability(Product $product): bool
    {
        $product->update(['is_available' => !$product->is_available]);
        return (bool) $product->is_available;
    }

    /**
     * Save branch/location pricing & availability override for a product.
     */
    public function saveLocationOverride(Product $product, array $data): LocationProductOverride
    {
        return LocationProductOverride::updateOrCreate(
            [
                'location_id' => $data['location_id'],
                'product_id' => $product->id,
            ],
            [
                'override_price' => $data['override_price'] ?? null,
                'is_available' => (bool) $data['is_available'],
            ]
        );
    }

    /**
     * Delete a product and its associated media file.
     */
    public function deleteProduct(Product $product): void
    {
        $product->deleteImageFile();
        $product->delete();
    }

    /**
     * Helper to store uploaded file or return fallback image URL.
     */
    protected function resolveProductImage(?UploadedFile $imageFile, ?string $fallbackUrl, ?string $defaultUrl): ?string
    {
        if ($imageFile && $imageFile->isValid()) {
            $path = $imageFile->store('products', 'public');
            return '/storage/' . $path;
        }

        if (!empty($fallbackUrl)) {
            return $fallbackUrl;
        }

        return $defaultUrl;
    }
}
