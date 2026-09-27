<?php

namespace App\Services;

use App\Exceptions\StorageQuotaExceededException;
use App\Models\Category;
use App\Models\Location;
use App\Models\LocationProductOverride;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Vendor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class MenuManagementService
{
    protected StorageService $storageService;

    public function __construct(
        ?StorageService $storageService = null
    ) {
        $this->storageService = $storageService ?? app(StorageService::class);
    }

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
            vendor: $vendor,
            fallbackUrl: $data['image'] ?? null,
            defaultUrl: Product::DEFAULT_IMAGE
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
            'discount_price' => ! empty($data['discount_price']) ? $data['discount_price'] : null,
            'discount_days' => ! empty($data['discount_days']) ? $data['discount_days'] : null,
            'discount_start_time' => ! empty($data['discount_start_time']) ? $data['discount_start_time'] : null,
            'discount_end_time' => ! empty($data['discount_end_time']) ? $data['discount_end_time'] : null,
            'is_discount_active' => array_key_exists('is_discount_active', $data) ? (bool) $data['is_discount_active'] : true,
            'image' => $imageUrl,
            'dietary_tags' => $data['dietary_tags'] ?? [],
            'calories' => $data['calories'] ?? null,
            'protein_g' => $data['protein_g'] ?? null,
            'carbs_g' => $data['carbs_g'] ?? null,
            'fat_g' => $data['fat_g'] ?? null,
            'preparation_time_min' => $data['preparation_time_min'] ?? null,
            'is_featured' => ! empty($data['is_featured']),
            'ai_priority' => ! empty($data['ai_priority']),
            'ai_priority_level' => (int) ($data['ai_priority_level'] ?? 0),
            'ai_group' => ! empty($data['ai_group']) ? $data['ai_group'] : null,
            'ai_tags' => $data['ai_tags'] ?? [],
            'ai_spicy_level' => (int) ($data['ai_spicy_level'] ?? 0),
            'ai_pairs_with' => $data['ai_pairs_with'] ?? [],
            'ai_enabled' => array_key_exists('ai_enabled', $data) ? (bool) $data['ai_enabled'] : true,
            'is_available' => true,
            'sort_order' => (int) (Product::where('category_id', $data['category_id'])->max('sort_order') ?? 0) + 1,
        ]);

        if (! empty($data['allergens'])) {
            $product->allergens()->sync($data['allergens']);
        }

        if (! empty($data['variations']) && is_array($data['variations'])) {
            $hasDefault = false;
            foreach ($data['variations'] as $idx => $vData) {
                if (empty($vData['name'])) {
                    continue;
                }
                $isDef = ! empty($vData['is_default']) || (! $hasDefault && $idx === 0);
                if ($isDef) {
                    $hasDefault = true;
                }
                $varName = trim($vData['name']);
                $hyName = ! empty($vData['hy_name']) ? trim($vData['hy_name']) : (! empty($vData['name_translations']['hy']) ? trim($vData['name_translations']['hy']) : $varName);
                $ruName = ! empty($vData['ru_name']) ? trim($vData['ru_name']) : (! empty($vData['name_translations']['ru']) ? trim($vData['name_translations']['ru']) : $varName);

                ProductVariation::create([
                    'product_id' => $product->id,
                    'name' => $varName,
                    'name_translations' => [
                        'en' => $varName,
                        'hy' => $hyName,
                        'ru' => $ruName,
                    ],
                    'price' => (float) ($vData['price'] ?? $data['price']),
                    'is_default' => $isDef,
                ]);
            }
        } else {
            // Initialize default standard portion variation
            ProductVariation::create([
                'product_id' => $product->id,
                'name' => 'Standard Portion',
                'name_translations' => [
                    'en' => 'Standard Portion',
                    'hy' => 'Ստանդարտ չափաբաժին',
                    'ru' => 'Стандартная порция',
                ],
                'price' => $data['price'],
                'is_default' => true,
            ]);
        }

        return $product;
    }

    /**
     * Update an existing product, handling image replacement and allergen relationships.
     */
    public function updateProduct(Product $product, array $data, ?UploadedFile $imageFile = null): Product
    {
        if ($imageFile && $imageFile->isValid()) {
            try {
                $storageFile = $this->storageService->replace(
                    oldPathOrUuid: $product->image,
                    newFile: $imageFile,
                    namespace: 'products',
                    vendor: $product->vendor,
                    entity: $product
                );
                $imageUrl = $storageFile->getUrl();
            } catch (StorageQuotaExceededException $e) {
                throw ValidationException::withMessages([
                    'image_file' => $e->getMessage(),
                ]);
            } catch (\Throwable $e) {
                Log::error('Product image replacement failed: '.$e->getMessage(), ['exception' => $e]);

                throw ValidationException::withMessages([
                    'image_file' => 'Նկարի փոխարինումը ձախողվեց: '.$e->getMessage(),
                ]);
            }
        } else {
            $imageUrl = ! empty($data['image']) ? $data['image'] : ($product->image ?: Product::DEFAULT_IMAGE);
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
            'discount_price' => ! empty($data['discount_price']) ? $data['discount_price'] : null,
            'discount_days' => ! empty($data['discount_days']) ? $data['discount_days'] : null,
            'discount_start_time' => ! empty($data['discount_start_time']) ? $data['discount_start_time'] : null,
            'discount_end_time' => ! empty($data['discount_end_time']) ? $data['discount_end_time'] : null,
            'is_discount_active' => array_key_exists('is_discount_active', $data) ? (bool) $data['is_discount_active'] : true,
            'image' => $imageUrl,
            'dietary_tags' => $data['dietary_tags'] ?? [],
            'calories' => $data['calories'] ?? null,
            'protein_g' => $data['protein_g'] ?? null,
            'carbs_g' => $data['carbs_g'] ?? null,
            'fat_g' => $data['fat_g'] ?? null,
            'preparation_time_min' => $data['preparation_time_min'] ?? null,
            'is_featured' => ! empty($data['is_featured']),
        ];

        if (array_key_exists('ai_priority', $data)) {
            $updatePayload['ai_priority'] = (bool) $data['ai_priority'];
        }
        if (array_key_exists('ai_priority_level', $data)) {
            $updatePayload['ai_priority_level'] = (int) $data['ai_priority_level'];
        }
        if (array_key_exists('ai_group', $data)) {
            $updatePayload['ai_group'] = ! empty($data['ai_group']) ? $data['ai_group'] : null;
        }
        if (array_key_exists('ai_tags', $data)) {
            $updatePayload['ai_tags'] = (array) $data['ai_tags'];
        }
        if (array_key_exists('ai_spicy_level', $data)) {
            $updatePayload['ai_spicy_level'] = (int) $data['ai_spicy_level'];
        }
        if (array_key_exists('ai_pairs_with', $data)) {
            $updatePayload['ai_pairs_with'] = (array) $data['ai_pairs_with'];
        }
        if (array_key_exists('ai_enabled', $data)) {
            $updatePayload['ai_enabled'] = (bool) $data['ai_enabled'];
        }

        if (array_key_exists('is_available', $data)) {
            $updatePayload['is_available'] = (bool) $data['is_available'];
        }

        $product->update($updatePayload);

        if (! empty($data['allergens'])) {
            $product->allergens()->sync($data['allergens']);
        } else {
            $product->allergens()->detach();
        }

        if (array_key_exists('variations', $data) && is_array($data['variations'])) {
            $keptIds = [];
            $hasDefault = false;
            foreach ($data['variations'] as $v) {
                if (! empty($v['is_default'])) {
                    $hasDefault = true;
                    break;
                }
            }

            foreach ($data['variations'] as $idx => $varData) {
                if (empty($varData['name'])) {
                    continue;
                }
                $isDef = ! empty($varData['is_default']) || (! $hasDefault && $idx === 0);
                if ($isDef) {
                    $hasDefault = true;
                }

                $varName = trim($varData['name']);
                $hyName = ! empty($varData['hy_name']) ? trim($varData['hy_name']) : (! empty($varData['name_translations']['hy']) ? trim($varData['name_translations']['hy']) : $varName);
                $ruName = ! empty($varData['ru_name']) ? trim($varData['ru_name']) : (! empty($varData['name_translations']['ru']) ? trim($varData['name_translations']['ru']) : $varName);

                $transData = [
                    'en' => $varName,
                    'hy' => $hyName,
                    'ru' => $ruName,
                ];

                $varId = ! empty($varData['id']) ? (int) $varData['id'] : null;
                $existing = $varId ? ProductVariation::where('product_id', $product->id)->find($varId) : null;

                if ($existing) {
                    $existing->update([
                        'name' => $varName,
                        'name_translations' => $transData,
                        'price' => (float) ($varData['price'] ?? $product->price),
                        'is_default' => $isDef,
                    ]);
                    $keptIds[] = $existing->id;
                } else {
                    $newVar = ProductVariation::create([
                        'product_id' => $product->id,
                        'name' => $varName,
                        'name_translations' => $transData,
                        'price' => (float) ($varData['price'] ?? $product->price),
                        'is_default' => $isDef,
                    ]);
                    $keptIds[] = $newVar->id;
                }
            }

            if (! empty($keptIds)) {
                ProductVariation::where('product_id', $product->id)
                    ->whereNotIn('id', $keptIds)
                    ->delete();
            }
        }

        return $product;
    }

    /**
     * Toggle product availability on/off.
     */
    public function toggleProductAvailability(Product $product): bool
    {
        $product->update(['is_available' => ! $product->is_available]);

        return (bool) $product->is_available;
    }

    /**
     * Save branch/location pricing & availability override for a product.
     */
    public function saveLocationOverride(Product $product, array $data): LocationProductOverride
    {
        $location = Location::findOrFail($data['location_id']);

        if ((int) $location->vendor_id !== (int) $product->vendor_id) {
            throw new \InvalidArgumentException('Cross-vendor override attempt: location and product do not belong to the same vendor.');
        }

        return LocationProductOverride::updateOrCreate(
            [
                'vendor_id' => $product->vendor_id,
                'location_id' => $location->id,
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
    protected function resolveProductImage(?UploadedFile $imageFile, ?Vendor $vendor, ?string $fallbackUrl, ?string $defaultUrl): ?string
    {
        if ($imageFile && $imageFile->isValid()) {
            try {
                $storageFile = $this->storageService->store(
                    file: $imageFile,
                    namespace: 'products',
                    vendor: $vendor
                );

                return $storageFile->getUrl();
            } catch (StorageQuotaExceededException $e) {
                throw ValidationException::withMessages([
                    'image_file' => $e->getMessage(),
                ]);
            } catch (\Throwable $e) {
                Log::error('Product image upload failed: '.$e->getMessage(), ['exception' => $e]);

                throw ValidationException::withMessages([
                    'image_file' => 'Նկարի վերբեռնումը ձախողվեց: '.$e->getMessage(),
                ]);
            }
        }

        if (! empty($fallbackUrl)) {
            return $fallbackUrl;
        }

        return $defaultUrl;
    }
}
