<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Allergen;
use App\Models\LocationProductOverride;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MenuBuilderController extends Controller
{
    public function index(Request $request)
    {
        $vendor = Auth::user()->vendor;
        $categories = Category::where('vendor_id', $vendor->id)
            ->with(['products.variations', 'products.allergens', 'products.overrides'])
            ->orderBy('sort_order', 'asc')
            ->get();
            
        $allergens = Allergen::all();
        $locations = $vendor->locations;

        return view('admin.menu.index', compact('vendor', 'categories', 'allergens', 'locations'));
    }

    public function storeCategory(Request $request)
    {
        $vendor = Auth::user()->vendor;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'hy_name' => 'nullable|string',
            'ru_name' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        Category::create([
            'vendor_id' => $vendor->id,
            'name' => $validated['name'],
            'name_translations' => [
                'hy' => $validated['hy_name'] ?? $validated['name'],
                'en' => $validated['name'],
                'ru' => $validated['ru_name'] ?? $validated['name'],
            ],
            'description' => $validated['description'] ?? null,
            'sort_order' => Category::where('vendor_id', $vendor->id)->max('sort_order') + 1,
            'is_active' => true,
        ]);

        return back()->with('success', 'Category added successfully!');
    }

    public function updateCategory(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'hy_name' => 'nullable|string',
            'ru_name' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $category->update([
            'name' => $validated['name'],
            'name_translations' => [
                'hy' => $validated['hy_name'] ?? $validated['name'],
                'en' => $validated['name'],
                'ru' => $validated['ru_name'] ?? $validated['name'],
            ],
            'description' => $validated['description'] ?? null,
        ]);

        return back()->with('success', 'Category updated successfully!');
    }

    public function destroyCategory(Category $category)
    {
        $category->delete();
        return back()->with('success', 'Category deleted successfully.');
    }

    public function storeProduct(Request $request)
    {
        $vendor = Auth::user()->vendor;

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'hy_name' => 'nullable|string',
            'ru_name' => 'nullable|string',
            'description' => 'nullable|string',
            'hy_description' => 'nullable|string',
            'ru_description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|string',
            'dietary_tags' => 'nullable|array',
            'allergens' => 'nullable|array',
            'calories' => 'nullable|integer',
            'protein_g' => 'nullable|numeric',
            'carbs_g' => 'nullable|numeric',
            'fat_g' => 'nullable|numeric',
            'preparation_time_min' => 'nullable|integer',
            'is_featured' => 'nullable|boolean',
        ]);

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'name_translations' => [
                'hy' => $validated['hy_name'] ?? $validated['name'],
                'en' => $validated['name'],
                'ru' => $validated['ru_name'] ?? $validated['name'],
            ],
            'description' => $validated['description'] ?? null,
            'description_translations' => [
                'hy' => $validated['hy_description'] ?? ($validated['description'] ?? null),
                'en' => $validated['description'] ?? null,
                'ru' => $validated['ru_description'] ?? ($validated['description'] ?? null),
            ],
            'price' => $validated['price'],
            'image' => $validated['image'] ?? 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=600&q=80',
            'dietary_tags' => $validated['dietary_tags'] ?? [],
            'calories' => $validated['calories'] ?? null,
            'protein_g' => $validated['protein_g'] ?? null,
            'carbs_g' => $validated['carbs_g'] ?? null,
            'fat_g' => $validated['fat_g'] ?? null,
            'preparation_time_min' => $validated['preparation_time_min'] ?? null,
            'is_featured' => $request->boolean('is_featured'),
            'is_available' => true,
            'sort_order' => Product::where('category_id', $validated['category_id'])->max('sort_order') + 1,
        ]);

        if (!empty($validated['allergens'])) {
            $product->allergens()->sync($validated['allergens']);
        }

        // Default variation
        ProductVariation::create([
            'product_id' => $product->id,
            'name' => 'Standard Portion',
            'price' => $validated['price'],
            'is_default' => true,
        ]);

        return back()->with('success', 'Product dish created successfully!');
    }

    public function updateProduct(Request $request, Product $product)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'hy_name' => 'nullable|string',
            'ru_name' => 'nullable|string',
            'description' => 'nullable|string',
            'hy_description' => 'nullable|string',
            'ru_description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|string',
            'dietary_tags' => 'nullable|array',
            'allergens' => 'nullable|array',
            'calories' => 'nullable|integer',
            'protein_g' => 'nullable|numeric',
            'carbs_g' => 'nullable|numeric',
            'fat_g' => 'nullable|numeric',
            'preparation_time_min' => 'nullable|integer',
            'is_featured' => 'nullable|boolean',
            'is_available' => 'nullable|boolean',
        ]);

        $product->update([
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'name_translations' => [
                'hy' => $validated['hy_name'] ?? $validated['name'],
                'en' => $validated['name'],
                'ru' => $validated['ru_name'] ?? $validated['name'],
            ],
            'description' => $validated['description'] ?? null,
            'description_translations' => [
                'hy' => $validated['hy_description'] ?? ($validated['description'] ?? null),
                'en' => $validated['description'] ?? null,
                'ru' => $validated['ru_description'] ?? ($validated['description'] ?? null),
            ],
            'price' => $validated['price'],
            'image' => !empty($validated['image']) ? $validated['image'] : ($product->image ?? 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=600&q=80'),
            'dietary_tags' => $validated['dietary_tags'] ?? [],
            'calories' => $validated['calories'] ?? null,
            'protein_g' => $validated['protein_g'] ?? null,
            'carbs_g' => $validated['carbs_g'] ?? null,
            'fat_g' => $validated['fat_g'] ?? null,
            'preparation_time_min' => $validated['preparation_time_min'] ?? null,
            'is_featured' => $request->boolean('is_featured'),
            'is_available' => $request->has('is_available') ? $request->boolean('is_available') : $product->is_available,
        ]);

        if (isset($validated['allergens'])) {
            $product->allergens()->sync($validated['allergens']);
        } else {
            $product->allergens()->detach();
        }

        return back()->with('success', "Dish {$product->name} updated successfully!");
    }

    public function toggleAvailability(Request $request, Product $product)
    {
        $product->update(['is_available' => !$product->is_available]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'is_available' => $product->is_available]);
        }

        return back()->with('success', "Dish '{$product->name}' stock status updated.");
    }

    public function saveOverride(Request $request, Product $product)
    {
        $validated = $request->validate([
            'location_id' => 'required|exists:locations,id',
            'override_price' => 'nullable|numeric',
            'is_available' => 'required|boolean',
        ]);

        LocationProductOverride::updateOrCreate(
            ['location_id' => $validated['location_id'], 'product_id' => $product->id],
            ['override_price' => $validated['override_price'], 'is_available' => $validated['is_available']]
        );

        return back()->with('success', 'Location product settings updated!');
    }

    public function destroyProduct(Product $product)
    {
        $product->delete();
        return back()->with('success', 'Product deleted successfully.');
    }
}
