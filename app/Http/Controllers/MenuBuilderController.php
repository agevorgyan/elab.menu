<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Allergen;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Services\MenuManagementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

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

    public function storeCategory(StoreCategoryRequest $request)
    {
        $validated = $request->validated();

        $this->menuService->createCategory(Auth::user()->vendor, $validated);

        return back()->with('success', 'Category added successfully!');
    }

    public function updateCategory(UpdateCategoryRequest $request, Category $category)
    {

        $validated = $request->validated();

        $this->menuService->updateCategory($category, $validated);

        return back()->with('success', 'Category updated successfully!');
    }

    public function destroyCategory(Category $category)
    {

        $this->menuService->deleteCategory($category);

        return back()->with('success', 'Category deleted successfully.');
    }

    public function storeProduct(StoreProductRequest $request)
    {
        $validated = $request->validated();

        try {
            $this->menuService->createProduct(Auth::user()->vendor, $validated, $request->file('image_file'));
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Failed to create product dish: '.$e->getMessage(), ['exception' => $e]);

            return back()->withInput()->with('error', 'Սխալ՝ ուտեստը ստեղծելիս: '.$e->getMessage());
        }

        return back()->with('success', 'Product dish created successfully!');
    }

    public function updateProduct(UpdateProductRequest $request, Product $product)
    {

        $validated = $request->validated();

        try {
            $this->menuService->updateProduct($product, $validated, $request->file('image_file'));
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error("Failed to update product {$product->id}: ".$e->getMessage(), ['exception' => $e]);

            return back()->withInput()->with('error', 'Սխալ՝ ուտեստը թարմացնելիս: '.$e->getMessage());
        }

        return back()->with('success', "Dish {$product->name} updated successfully!");
    }

    public function toggleAvailability(Request $request, Product $product)
    {

        $isAvailable = $this->menuService->toggleProductAvailability($product);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'is_available' => $isAvailable]);
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

        $location = Location::find($validated['location_id']);

        $this->menuService->saveLocationOverride($product, $validated);

        return back()->with('success', 'Location product settings updated!');
    }

    public function destroyProduct(Product $product)
    {

        $this->menuService->deleteProduct($product);

        return back()->with('success', 'Product deleted successfully.');
    }
}
