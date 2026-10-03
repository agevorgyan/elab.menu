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
        $translations = [
            'en' => $name,
        ];
        if (! empty($data['hy_name'])) {
            $translations['hy'] = $data['hy_name'];
        }
        if (! empty($data['name_translations']) && is_array($data['name_translations'])) {
            foreach ($data['name_translations'] as $code => $val) {
                if (! empty($val)) {
                    $translations[$code] = trim($val);
                }
            }
        }
        if (! empty($data['ru_name'])) {
            $translations['ru'] = $data['ru_name'];
        }

        $category = Category::create([
            'vendor_id' => $vendor->id,
            'name' => $name,
            'name_translations' => $translations,
            'description' => $data['description'] ?? null,
            'sort_order' => (int) (Category::where('vendor_id', $vendor->id)->max('sort_order') ?? 0) + 1,
            'is_active' => true,
        ]);

        TenantCache::invalidateMenu($vendor);

        return $category;
    }

    /**
     * Update existing category.
     */
    public function updateCategory(Category $category, array $data): Category
    {
        $name = $data['name'];
        $translations = is_array($category->name_translations) ? $category->name_translations : [];
        $translations['en'] = $name;
        if (! empty($data['hy_name'])) {
            $translations['hy'] = $data['hy_name'];
        }

        if (! empty($data['name_translations']) && is_array($data['name_translations'])) {
            foreach ($data['name_translations'] as $code => $val) {
                if (! empty($val)) {
                    $translations[$code] = trim($val);
                }
            }
        }
        if (! empty($data['ru_name'])) {
            $translations['ru'] = $data['ru_name'];
        }

        $category->update([
            'name' => $name,
            'name_translations' => $translations,
            'description' => $data['description'] ?? null,
        ]);

        TenantCache::invalidateMenu($category->vendor_id);

        return $category;
    }

    /**
     * Delete a category.
     */
    public function deleteCategory(Category $category): void
    {
        $vendorId = $category->vendor_id;
        $category->delete();
        TenantCache::invalidateMenu($vendorId);
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

        $nameTranslations = [
            'en' => $name,
        ];
        if (! empty($data['hy_name'])) {
            $nameTranslations['hy'] = $data['hy_name'];
        }
        if (! empty($data['name_translations']) && is_array($data['name_translations'])) {
            foreach ($data['name_translations'] as $code => $val) {
                if (! empty($val)) {
                    $nameTranslations[$code] = trim($val);
                }
            }
        }
        if (! empty($data['ru_name'])) {
            $nameTranslations['ru'] = $data['ru_name'];
        }

        $descTranslations = [
            'en' => $desc,
        ];
        if (! empty($data['hy_description'])) {
            $descTranslations['hy'] = $data['hy_description'];
        }
        if (! empty($data['description_translations']) && is_array($data['description_translations'])) {
            foreach ($data['description_translations'] as $code => $val) {
                if (! empty($val)) {
                    $descTranslations[$code] = trim($val);
                }
            }
        }
        if (! empty($data['ru_description'])) {
            $descTranslations['ru'] = $data['ru_description'];
        }

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $data['category_id'],
            'name' => $name,
            'name_translations' => $nameTranslations,
            'description' => $desc,
            'description_translations' => $descTranslations,
            'price' => $data['price'],
            'discount_price' => ! empty($data['discount_price']) ? $data['discount_price'] : null,
            'discount_days' => ! empty($data['discount_days']) ? $data['discount_days'] : null,
            'discount_start_time' => ! empty($data['discount_start_time']) ? $data['discount_start_time'] : null,
            'discount_end_time' => ! empty($data['discount_end_time']) ? $data['discount_end_time'] : null,
            'is_discount_active' => array_key_exists('is_discount_active', $data) ? (bool) $data['is_discount_active'] : true,
            'available_start_time' => ! empty($data['available_start_time']) ? $data['available_start_time'] : null,
            'available_end_time' => ! empty($data['available_end_time']) ? $data['available_end_time'] : null,
            'available_days' => ! empty($data['available_days']) ? $data['available_days'] : null,
            'available_for_dine_in' => array_key_exists('available_for_dine_in', $data) ? (bool) $data['available_for_dine_in'] : true,
            'available_for_takeaway' => array_key_exists('available_for_takeaway', $data) ? (bool) $data['available_for_takeaway'] : true,
            'available_for_delivery' => array_key_exists('available_for_delivery', $data) ? (bool) $data['available_for_delivery'] : true,
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

        if (array_key_exists('locations', $data)) {
            $this->syncLocationAvailability($product, $vendor, $data['locations']);
        }

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
                $transData = [
                    'en' => $varName,
                ];
                if (! empty($vData['hy_name'])) {
                    $transData['hy'] = trim($vData['hy_name']);
                } elseif (! empty($vData['name_translations']['hy'])) {
                    $transData['hy'] = trim($vData['name_translations']['hy']);
                }
                if (! empty($vData['name_translations']) && is_array($vData['name_translations'])) {
                    foreach ($vData['name_translations'] as $code => $val) {
                        if (! empty($val)) {
                            $transData[$code] = trim($val);
                        }
                    }
                }
                if (! empty($vData['ru_name'])) {
                    $transData['ru'] = trim($vData['ru_name']);
                }

                ProductVariation::create([
                    'product_id' => $product->id,
                    'name' => $varName,
                    'name_translations' => $transData,
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
                ],
                'price' => $data['price'],
                'is_default' => true,
            ]);
        }

        TenantCache::invalidateMenu($vendor);

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

        $nameTranslations = is_array($product->name_translations) ? $product->name_translations : [];
        $nameTranslations['en'] = $name;
        if (! empty($data['hy_name'])) {
            $nameTranslations['hy'] = $data['hy_name'];
        }
        if (! empty($data['name_translations']) && is_array($data['name_translations'])) {
            foreach ($data['name_translations'] as $code => $val) {
                if (! empty($val)) {
                    $nameTranslations[$code] = trim($val);
                }
            }
        }
        if (! empty($data['ru_name'])) {
            $nameTranslations['ru'] = $data['ru_name'];
        }

        $descTranslations = is_array($product->description_translations) ? $product->description_translations : [];
        $descTranslations['en'] = $desc;
        if (! empty($data['hy_description'])) {
            $descTranslations['hy'] = $data['hy_description'];
        }
        if (! empty($data['description_translations']) && is_array($data['description_translations'])) {
            foreach ($data['description_translations'] as $code => $val) {
                if (! empty($val)) {
                    $descTranslations[$code] = trim($val);
                }
            }
        }
        if (! empty($data['ru_description'])) {
            $descTranslations['ru'] = $data['ru_description'];
        }

        $updatePayload = [
            'category_id' => $data['category_id'],
            'name' => $name,
            'name_translations' => $nameTranslations,
            'description' => $desc,
            'description_translations' => $descTranslations,
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

        if (array_key_exists('available_start_time', $data)) {
            $updatePayload['available_start_time'] = ! empty($data['available_start_time']) ? $data['available_start_time'] : null;
        }
        if (array_key_exists('available_end_time', $data)) {
            $updatePayload['available_end_time'] = ! empty($data['available_end_time']) ? $data['available_end_time'] : null;
        }
        if (array_key_exists('available_days', $data)) {
            $updatePayload['available_days'] = ! empty($data['available_days']) ? $data['available_days'] : null;
        }
        if (array_key_exists('available_for_dine_in', $data)) {
            $updatePayload['available_for_dine_in'] = (bool) $data['available_for_dine_in'];
        }
        if (array_key_exists('available_for_takeaway', $data)) {
            $updatePayload['available_for_takeaway'] = (bool) $data['available_for_takeaway'];
        }
        if (array_key_exists('available_for_delivery', $data)) {
            $updatePayload['available_for_delivery'] = (bool) $data['available_for_delivery'];
        }

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

        if (array_key_exists('locations', $data)) {
            $this->syncLocationAvailability($product, $product->vendor, $data['locations']);
        }

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
                $transData = [
                    'en' => $varName,
                ];
                if (! empty($varData['hy_name'])) {
                    $transData['hy'] = trim($varData['hy_name']);
                } elseif (! empty($varData['name_translations']['hy'])) {
                    $transData['hy'] = trim($varData['name_translations']['hy']);
                }
                if (! empty($varData['name_translations']) && is_array($varData['name_translations'])) {
                    foreach ($varData['name_translations'] as $code => $val) {
                        if (! empty($val)) {
                            $transData[$code] = trim($val);
                        }
                    }
                }
                if (! empty($varData['ru_name'])) {
                    $transData['ru'] = trim($varData['ru_name']);
                }

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

        TenantCache::invalidateMenu($product->vendor_id);

        return $product;
    }

    /**
     * Synchronize branch availability overrides for a product.
     */
    public function syncLocationAvailability(Product $product, Vendor $vendor, ?array $selectedLocationIds): void
    {
        if ($selectedLocationIds === null) {
            return;
        }

        $locations = Location::where('vendor_id', $vendor->id)->get();
        $selectedInts = array_map('intval', $selectedLocationIds);

        foreach ($locations as $loc) {
            $isAvailable = in_array((int) $loc->id, $selectedInts, true);

            $override = LocationProductOverride::firstOrNew([
                'vendor_id' => $vendor->id,
                'location_id' => $loc->id,
                'product_id' => $product->id,
            ]);
            $override->is_available = $isAvailable;
            $override->save();
        }
    }

    /**
     * Toggle product availability on/off (either globally or for a specific branch).
     */
    public function toggleProductAvailability(Product $product, ?int $locationId = null): bool
    {
        if ($locationId) {
            $override = LocationProductOverride::where('vendor_id', $product->vendor_id)
                ->where('location_id', $locationId)
                ->where('product_id', $product->id)
                ->first();

            $currentStatus = $override !== null ? (bool) $override->is_available : (bool) $product->is_available;
            $newStatus = ! $currentStatus;

            $locOverride = LocationProductOverride::firstOrNew([
                'vendor_id' => $product->vendor_id,
                'location_id' => $locationId,
                'product_id' => $product->id,
            ]);
            $locOverride->is_available = $newStatus;
            $locOverride->save();

            TenantCache::invalidateMenu($product->vendor_id);

            return $newStatus;
        }

        $product->update(['is_available' => ! $product->is_available]);
        TenantCache::invalidateMenu($product->vendor_id);

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

        $override = LocationProductOverride::updateOrCreate(
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

        TenantCache::invalidateMenu($product->vendor_id);

        return $override;
    }

    /**
     * Delete a product and its associated media file.
     */
    public function deleteProduct(Product $product): void
    {
        $vendorId = $product->vendor_id;
        $product->deleteImageFile();
        $product->delete();
        TenantCache::invalidateMenu($vendorId);
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
