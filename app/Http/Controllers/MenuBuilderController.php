<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Allergen;
use App\Models\Location;
use App\Services\MenuManagementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MenuBuilderController extends Controller
{
    public function __construct(
        protected MenuManagementService $menuService
    ) {}

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
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'hy_name' => 'nullable|string',
            'ru_name' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $this->menuService->createCategory(Auth::user()->vendor, $validated);

        return back()->with('success', 'Category added successfully!');
    }

    public function updateCategory(Request $request, Category $category)
    {
        if ($category->vendor_id !== Auth::user()->vendor_id) {
            abort(403, 'Unauthorized category action.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'hy_name' => 'nullable|string',
            'ru_name' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $this->menuService->updateCategory($category, $validated);

        return back()->with('success', 'Category updated successfully!');
    }

    public function destroyCategory(Category $category)
    {
        if ($category->vendor_id !== Auth::user()->vendor_id) {
            abort(403, 'Unauthorized category action.');
        }

        $this->menuService->deleteCategory($category);
        return back()->with('success', 'Category deleted successfully.');
    }

    public function storeProduct(Request $request)
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
            'image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:5120',
            'dietary_tags' => 'nullable|array',
            'allergens' => 'nullable|array',
            'calories' => 'nullable|integer',
            'protein_g' => 'nullable|numeric',
            'carbs_g' => 'nullable|numeric',
            'fat_g' => 'nullable|numeric',
            'preparation_time_min' => 'nullable|integer',
            'is_featured' => 'nullable|boolean',
        ]);

        $category = Category::find($validated['category_id']);
        if (!$category || $category->vendor_id !== Auth::user()->vendor_id) {
            abort(403, 'Unauthorized category selection.');
        }

        $this->menuService->createProduct(Auth::user()->vendor, $validated, $request->file('image_file'));

        return back()->with('success', 'Product dish created successfully!');
    }

    public function updateProduct(Request $request, Product $product)
    {
        if ($product->vendor_id !== Auth::user()->vendor_id) {
            abort(403, 'Unauthorized product action.');
        }

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
            'image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:5120',
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

        $category = Category::find($validated['category_id']);
        if (!$category || $category->vendor_id !== Auth::user()->vendor_id) {
            abort(403, 'Unauthorized category selection.');
        }

        $this->menuService->updateProduct($product, $validated, $request->file('image_file'));

        return back()->with('success', "Dish {$product->name} updated successfully!");
    }

    public function toggleAvailability(Request $request, Product $product)
    {
        if ($product->vendor_id !== Auth::user()->vendor_id) {
            abort(403, 'Unauthorized product action.');
        }

        $isAvailable = $this->menuService->toggleProductAvailability($product);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'is_available' => $isAvailable]);
        }

        return back()->with('success', "Dish '{$product->name}' stock status updated.");
    }

    public function saveOverride(Request $request, Product $product)
    {
        if ($product->vendor_id !== Auth::user()->vendor_id) {
            abort(403, 'Unauthorized product action.');
        }

        $validated = $request->validate([
            'location_id' => 'required|exists:locations,id',
            'override_price' => 'nullable|numeric',
            'is_available' => 'required|boolean',
        ]);

        $location = Location::find($validated['location_id']);
        if (!$location || $location->vendor_id !== Auth::user()->vendor_id) {
            abort(403, 'Unauthorized location action.');
        }

        $this->menuService->saveLocationOverride($product, $validated);

        return back()->with('success', 'Location product settings updated!');
    }

    public function destroyProduct(Product $product)
    {
        if ($product->vendor_id !== Auth::user()->vendor_id) {
            abort(403, 'Unauthorized product action.');
        }

        $this->menuService->deleteProduct($product);
        return back()->with('success', 'Product deleted successfully.');
    }
}
