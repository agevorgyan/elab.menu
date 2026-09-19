<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Vendor;
use App\Services\AiWaiterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiWaiterController extends Controller
{
    public function __construct(
        public AiWaiterService $aiWaiterService
    ) {}

    /**
     * Get AI recommendations based on user quiz preferences or freeform prompt.
     */
    public function recommend(Request $request, string $vendor_slug): JsonResponse
    {
        $vendor = Vendor::where('slug', $vendor_slug)->where('is_active', true)->firstOrFail();

        $preferences = [
            'craving' => $request->input('craving'),
            'occasion' => $request->input('occasion'),
            'drink_preference' => $request->input('drink_preference'),
            'dietary' => (array) $request->input('dietary', []),
        ];

        $prompt = $request->input('prompt');
        $lang = $request->input('lang', session('app_locale', 'hy'));
        if (! in_array($lang, ['hy', 'en', 'ru'])) {
            $lang = 'hy';
        }

        $locationId = $request->input('location_id') ? (int) $request->input('location_id') : null;

        $result = $this->aiWaiterService->recommendDishes(
            $vendor,
            $preferences,
            $prompt,
            $lang,
            $locationId
        );

        return response()->json([
            'success' => true,
            'waiter_name' => $vendor->getAiWaiterName(),
            'commentary' => $result['commentary'],
            'recommendations' => $result['recommendations'],
        ]);
    }

    /**
     * Get food and drink pairings for a specific product.
     */
    public function pairings(Request $request, string $vendor_slug, int $productId): JsonResponse
    {
        $vendor = Vendor::where('slug', $vendor_slug)->where('is_active', true)->firstOrFail();
        $product = Product::where('vendor_id', $vendor->id)
            ->where('id', $productId)
            ->where('is_available', true)
            ->firstOrFail();

        $lang = $request->input('lang', session('app_locale', 'hy'));
        if (! in_array($lang, ['hy', 'en', 'ru'])) {
            $lang = 'hy';
        }

        $locationId = $request->input('location_id') ? (int) $request->input('location_id') : null;

        $allProducts = Product::where('vendor_id', $vendor->id)
            ->where('is_available', true)
            ->with(['category', 'variations', 'overrides'])
            ->get();

        $pairings = $this->aiWaiterService->findPairingsForProduct(
            $product,
            $allProducts,
            $vendor,
            $lang,
            $locationId
        );

        return response()->json([
            'success' => true,
            'product_id' => $product->id,
            'pairings' => $pairings,
        ]);
    }
}
