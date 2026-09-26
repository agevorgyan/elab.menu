<?php

namespace App\Http\Controllers;

use App\Models\AiWaiterSession;
use App\Models\Order;
use App\Models\Product;
use App\Models\Vendor;
use App\Services\AiWaiterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AiWaiterController extends Controller
{
    public function __construct(
        public AiWaiterService $aiWaiterService
    ) {}

    /**
     * Get public configuration for AI Waiter.
     */
    public function config(Request $request, string $vendor_slug): JsonResponse
    {
        $vendor = Vendor::where('slug', $vendor_slug)->where('is_active', true)->firstOrFail();
        $config = $vendor->getAiWaiterConfig();

        return response()->json([
            'success' => true,
            'enabled' => (bool) $vendor->ai_waiter_enabled,
            'waiter_name' => $vendor->getAiWaiterName(),
            'avatar' => $vendor->ai_waiter_avatar,
            'welcome_text' => $vendor->ai_waiter_welcome_text,
            'languages' => $vendor->getAiWaiterLanguages(),
            'auto_popup' => $config['auto_popup'] ?? true,
            'free_text_enabled' => $config['free_text_enabled'] ?? true,
            'ai_chat_enabled' => $config['ai_chat_enabled'] ?? true,
            'currency' => $vendor->currency,
        ]);
    }

    /**
     * Get question library for current language.
     */
    public function questions(Request $request, string $vendor_slug): JsonResponse
    {
        $vendor = Vendor::where('slug', $vendor_slug)->where('is_active', true)->firstOrFail();
        $lang = $this->resolveLanguage($request, $vendor);
        $questions = $this->aiWaiterService->getQuestionLibrary($lang);

        return response()->json([
            'success' => true,
            'lang' => $lang,
            'questions' => array_values($questions),
        ]);
    }

    /**
     * Start an AI Waiter session.
     */
    public function startSession(Request $request, string $vendor_slug): JsonResponse
    {
        $vendor = Vendor::where('slug', $vendor_slug)->where('is_active', true)->firstOrFail();

        $lang = $this->resolveLanguage($request, $vendor);
        $locationId = $request->input('location_id') ? (int) $request->input('location_id') : null;
        if ($locationId && ! $vendor->locations()->where('id', $locationId)->exists()) {
            $locationId = null;
        }
        $tableNumber = $request->input('table_number');

        $session = AiWaiterSession::create([
            'vendor_id' => $vendor->id,
            'location_id' => $locationId,
            'session_token' => (string) Str::uuid(),
            'table_number' => $tableNumber,
            'language' => $lang,
            'status' => 'started',
            'preferences' => [],
            'questions_history' => [],
            'answers_history' => [],
            'recommendations' => [],
            'cart_items' => [],
        ]);

        $nextQuestion = $this->aiWaiterService->getNextQuestion($vendor, [], [], $lang);

        return response()->json([
            'success' => true,
            'session_id' => $session->id,
            'session_token' => $session->session_token,
            'language' => $lang,
            'allowed_languages' => $vendor->getAiWaiterLanguages(),
            'waiter_name' => $vendor->getAiWaiterName(),
            'next_question' => $nextQuestion,
        ]);
    }

    /**
     * Set language on session.
     */
    public function setLanguage(Request $request, string $vendor_slug, int $id): JsonResponse
    {
        $vendor = Vendor::where('slug', $vendor_slug)->where('is_active', true)->firstOrFail();
        $session = AiWaiterSession::where('vendor_id', $vendor->id)->findOrFail($id);

        $lang = strtolower((string) $request->input('language', 'hy'));
        $allowed = $vendor->getAiWaiterLanguages();
        if (! in_array($lang, $allowed, true)) {
            $lang = $allowed[0] ?? 'hy';
        }

        $preferences = $session->preferences ?? [];
        $preferences['language'] = $lang;

        $session->update([
            'language' => $lang,
            'preferences' => $preferences,
        ]);

        session(['app_locale' => $lang, 'locale' => $lang]);

        $nextQuestion = $this->aiWaiterService->getNextQuestion(
            $vendor,
            $preferences,
            $session->questions_history ?? [],
            $lang
        );

        return response()->json([
            'success' => true,
            'session_id' => $session->id,
            'language' => $lang,
            'next_question' => $nextQuestion,
        ]);
    }

    /**
     * Submit an answer to a question.
     */
    public function submitAnswer(Request $request, string $vendor_slug, int $id): JsonResponse
    {
        $vendor = Vendor::where('slug', $vendor_slug)->where('is_active', true)->firstOrFail();
        $session = AiWaiterSession::where('vendor_id', $vendor->id)->findOrFail($id);

        $questionKey = (string) $request->input('question_key');
        $answerValue = $request->input('answer_value');
        $freeText = $request->input('free_text');

        $lang = $session->language ?? $this->resolveLanguage($request, $vendor);
        $preferences = $session->preferences ?? [];

        // Save question key to preferences
        if (! empty($questionKey)) {
            $preferences[$questionKey] = $answerValue;
        }

        // Parse free text if provided
        if (! empty($freeText)) {
            $parsed = $this->aiWaiterService->parseFreeText((string) $freeText, $lang, $vendor);
            $preferences = array_merge($preferences, $parsed);
        }

        $questionsHistory = (array) ($session->questions_history ?? []);
        if (! empty($questionKey) && ! in_array($questionKey, $questionsHistory, true)) {
            $questionsHistory[] = $questionKey;
        }

        $answersHistory = (array) ($session->answers_history ?? []);
        $answersHistory[] = [
            'question_key' => $questionKey,
            'answer_value' => $answerValue,
            'free_text' => $freeText,
            'answered_at' => now()->toISOString(),
        ];

        $nextQuestion = $this->aiWaiterService->getNextQuestion(
            $vendor,
            $preferences,
            $questionsHistory,
            $lang
        );

        $session->update([
            'preferences' => $preferences,
            'questions_history' => $questionsHistory,
            'answers_history' => $answersHistory,
            'status' => 'questions_in_progress',
        ]);

        return response()->json([
            'success' => true,
            'session_id' => $session->id,
            'is_ready_for_recommendations' => $nextQuestion === null,
            'next_question' => $nextQuestion,
            'preferences' => $preferences,
        ]);
    }

    /**
     * Get next question for this session.
     */
    public function nextQuestion(Request $request, string $vendor_slug, int $id): JsonResponse
    {
        $vendor = Vendor::where('slug', $vendor_slug)->where('is_active', true)->firstOrFail();
        $session = AiWaiterSession::where('vendor_id', $vendor->id)->findOrFail($id);

        $lang = $session->language ?? $this->resolveLanguage($request, $vendor);
        $next = $this->aiWaiterService->getNextQuestion(
            $vendor,
            $session->preferences ?? [],
            $session->questions_history ?? [],
            $lang
        );

        return response()->json([
            'success' => true,
            'session_id' => $session->id,
            'is_ready_for_recommendations' => $next === null,
            'next_question' => $next,
        ]);
    }

    /**
     * Generate and retrieve personalized recommendations for the session.
     */
    public function recommendations(Request $request, string $vendor_slug, int $id): JsonResponse
    {
        $vendor = Vendor::where('slug', $vendor_slug)->where('is_active', true)->firstOrFail();
        $session = AiWaiterSession::where('vendor_id', $vendor->id)->findOrFail($id);

        $lang = $session->language ?? $this->resolveLanguage($request, $vendor);
        $locationId = $session->location_id ?: ($request->input('location_id') ? (int) $request->input('location_id') : null);

        $result = $this->aiWaiterService->recommendDishes(
            $vendor,
            $session->preferences ?? [],
            $session->preferences['free_text'] ?? null,
            $lang,
            $locationId
        );

        $session->update([
            'status' => 'recommended',
            'recommendations' => [
                'main' => array_column($result['main_recommendations'], 'id'),
                'secondary' => array_column($result['secondary_recommendations'], 'id'),
                'pairing_drink' => $result['pairing_drink']['id'] ?? null,
                'bundle_items' => isset($result['bundle']['items']) ? array_column($result['bundle']['items'], 'id') : [],
            ],
        ]);

        return response()->json([
            'success' => true,
            'session_id' => $session->id,
            'waiter_name' => $vendor->getAiWaiterName(),
            'commentary' => $result['commentary'],
            'main_recommendations' => $result['main_recommendations'],
            'secondary_recommendations' => $result['secondary_recommendations'],
            'pairing_drink' => $result['pairing_drink'],
            'bundle' => $result['bundle'],
            // Backwards-compatible field
            'recommendations' => $result['main_recommendations'],
        ]);
    }

    /**
     * Conversational Q&A chat grounded strictly in menu items.
     */
    public function chat(Request $request, string $vendor_slug, int $id): JsonResponse
    {
        $vendor = Vendor::where('slug', $vendor_slug)->where('is_active', true)->firstOrFail();
        $session = AiWaiterSession::where('vendor_id', $vendor->id)->findOrFail($id);

        $message = trim((string) $request->input('message'));
        if ($message === '') {
            return response()->json([
                'success' => false,
                'message' => 'Message cannot be empty.',
            ], 422);
        }

        $lang = $session->language ?? $this->resolveLanguage($request, $vendor);
        $locationId = $session->location_id ?: ($request->input('location_id') ? (int) $request->input('location_id') : null);

        $chatResponse = $this->aiWaiterService->answerChatQuery(
            $vendor,
            $message,
            $session->toArray(),
            $lang,
            $locationId
        );

        return response()->json([
            'success' => true,
            'reply' => $chatResponse['reply'],
            'suggested_products' => $chatResponse['suggested_products'],
        ]);
    }

    /**
     * Track item or bundle addition to cart.
     */
    public function addToCart(Request $request, string $vendor_slug, int $id): JsonResponse
    {
        $vendor = Vendor::where('slug', $vendor_slug)->where('is_active', true)->firstOrFail();
        $session = AiWaiterSession::where('vendor_id', $vendor->id)->findOrFail($id);

        $productId = $request->input('product_id');
        $bundle = $request->input('bundle');
        $items = (array) ($session->cart_items ?? []);

        if (! empty($productId)) {
            $items[] = [
                'type' => 'product',
                'product_id' => $productId,
                'added_at' => now()->toISOString(),
            ];
        } elseif (! empty($bundle)) {
            $items[] = [
                'type' => 'bundle',
                'bundle_data' => $bundle,
                'added_at' => now()->toISOString(),
            ];
        }

        $session->update([
            'status' => 'added_to_cart',
            'cart_items' => $items,
        ]);

        return response()->json([
            'success' => true,
            'session_id' => $session->id,
            'cart_items_count' => count($items),
        ]);
    }

    /**
     * Mark AI session as completed or converted to an order.
     */
    public function complete(Request $request, string $vendor_slug, int $id): JsonResponse
    {
        $vendor = Vendor::where('slug', $vendor_slug)->where('is_active', true)->firstOrFail();
        $session = AiWaiterSession::where('vendor_id', $vendor->id)->findOrFail($id);

        $orderId = $request->input('order_id');
        $orderNumber = $request->input('order_number');
        $orderAmount = (float) $request->input('total_amount', 0);

        if (! empty($orderNumber) && empty($orderId)) {
            $order = Order::where('vendor_id', $vendor->id)->where('order_number', $orderNumber)->first();
            if ($order) {
                $orderId = $order->id;
                $orderAmount = (float) $order->total_amount;
            }
        }

        $session->update([
            'status' => $orderId ? 'order_placed' : 'completed',
            'order_id' => $orderId,
            'total_order_amount' => $orderAmount,
            'completed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'session_id' => $session->id,
            'status' => $session->status,
        ]);
    }

    /**
     * Legacy direct recommendation endpoint.
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
        $lang = $this->resolveLanguage($request, $vendor);
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
            'recommendations' => $result['main_recommendations'],
            'main_recommendations' => $result['main_recommendations'],
            'secondary_recommendations' => $result['secondary_recommendations'],
            'pairing_drink' => $result['pairing_drink'],
            'bundle' => $result['bundle'],
        ]);
    }

    /**
     * Legacy pairings endpoint for a product.
     */
    public function pairings(Request $request, string $vendor_slug, int $productId): JsonResponse
    {
        $vendor = Vendor::where('slug', $vendor_slug)->where('is_active', true)->firstOrFail();
        $product = Product::where('vendor_id', $vendor->id)
            ->where('id', $productId)
            ->where('is_available', true)
            ->firstOrFail();

        $lang = $this->resolveLanguage($request, $vendor);
        $locationId = $request->input('location_id') ? (int) $request->input('location_id') : null;

        $allProducts = Product::where('vendor_id', $vendor->id)
            ->where('is_available', true)
            ->with(['category', 'variations', 'overrides'])
            ->get();

        $result = $this->aiWaiterService->recommendDishes(
            $vendor,
            ['craving' => $product->category?->name],
            $product->name,
            $lang,
            $locationId
        );

        return response()->json([
            'success' => true,
            'product_id' => $product->id,
            'pairings' => [
                'drink' => $result['pairing_drink'],
                'side' => $result['secondary_recommendations'][0] ?? null,
                'pairing_note' => $result['commentary'] ?? '',
            ],
        ]);
    }

    /**
     * Helper to resolve supported language.
     */
    protected function resolveLanguage(Request $request, Vendor $vendor): string
    {
        $lang = $request->input('lang', $request->input('language', session('app_locale', 'hy')));
        $allowed = $vendor->getAiWaiterLanguages();

        if (in_array($lang, $allowed, true)) {
            return $lang;
        }

        return $allowed[0] ?? 'hy';
    }
}
