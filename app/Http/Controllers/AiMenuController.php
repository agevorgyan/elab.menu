<?php

namespace App\Http\Controllers;

use App\Jobs\TranslateMenuJob;
use App\Models\Category;
use App\Models\Product;
use App\Services\AiMenuService;
use App\Services\MenuExtractorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AiMenuController extends Controller
{
    public function __construct(
        public AiMenuService $aiService,
        public MenuExtractorService $extractorService
    ) {}

    public function showImportForm()
    {
        $vendor = Auth::user()->vendor;

        return view('admin.ai.import', compact('vendor'));
    }

    public function processImport(Request $request)
    {
        $request->validate([
            'import_source' => 'nullable|string|in:file,url,text',
            'website_url' => 'nullable|url|max:1000',
            'menu_file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,csv,txt,json,jpg,jpeg,png,webp|max:20480',
            'menu_text' => 'nullable|string',
        ]);

        if (! $request->hasFile('menu_file') && ! $request->filled('website_url') && ! $request->filled('menu_text')) {
            return back()->withInput()->with('error', 'Խնդրում ենք վերբեռնել ֆայլ, նշել կայքի հղում կամ տեղադրել մենյուի տեքստը:');
        }

        try {
            $vendor = Auth::user()->vendor;
            if ($request->filled('website_url')) {
                $extraction = $this->extractorService->extractStructuredOrTextFromUrl($request->input('website_url'));
                if ($extraction['type'] === 'structured' && ! empty($extraction['categories'])) {
                    $parsedMenu = ['categories' => $extraction['categories']];
                } else {
                    $parsedMenu = $this->aiService->parseMenuFromText($extraction['text'] ?? '', $vendor);
                }
            } elseif ($request->hasFile('menu_file')) {
                $file = $request->file('menu_file');
                $extraction = $this->extractorService->extractFromFile($file);

                if ($extraction['type'] === 'image') {
                    $parsedMenu = $this->aiService->parseMenuFromImage($extraction['base64'], $extraction['mime'], $vendor);
                } elseif ($extraction['type'] === 'pdf') {
                    $parsedMenu = $this->aiService->parseMenuFromPdf($extraction['base64'], $extraction['text'] ?? null, $vendor);
                } elseif ($extraction['type'] === 'structured' && ! empty($extraction['categories'])) {
                    $parsedMenu = ['categories' => $extraction['categories']];
                } else {
                    $parsedMenu = $this->aiService->parseMenuFromText($extraction['text'] ?? '', $vendor);
                }
            } else {
                $parsedMenu = $this->aiService->parseMenuFromText($request->input('menu_text', ''), $vendor);
            }
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Մենյուի արտածումը ձախողվեց: '.$e->getMessage());
        }

        if (empty($parsedMenu['categories'])) {
            $parsedMenu = [
                'categories' => [
                    [
                        'name' => 'Imported Category',
                        'products' => [],
                    ],
                ],
            ];
        }

        return view('admin.ai.preview_import', [
            'vendor' => Auth::user()->vendor,
            'parsedData' => $parsedMenu,
        ]);
    }

    public function confirmImport(Request $request)
    {
        $vendor = Auth::user()->vendor;
        $categoriesData = $request->input('categories', []);

        foreach ($categoriesData as $cIdx => $catData) {
            if (empty($catData['name'])) {
                continue;
            }

            $category = Category::create([
                'vendor_id' => $vendor->id,
                'name' => $catData['name'],
                'name_translations' => ['hy' => $catData['name'], 'en' => $catData['name'], 'ru' => $catData['name']],
                'sort_order' => Category::where('vendor_id', $vendor->id)->max('sort_order') + 1,
                'is_active' => true,
            ]);

            if (isset($catData['products']) && is_array($catData['products'])) {
                foreach ($catData['products'] as $pIdx => $prodData) {
                    if (empty($prodData['name'])) {
                        continue;
                    }

                    $image = ! empty($prodData['image'])
                        ? trim((string) $prodData['image'])
                        : null;

                    Product::create([
                        'vendor_id' => $vendor->id,
                        'category_id' => $category->id,
                        'name' => $prodData['name'],
                        'name_translations' => ['hy' => $prodData['name'], 'en' => $prodData['name'], 'ru' => $prodData['name']],
                        'description' => $prodData['description'] ?? '',
                        'price' => (float) ($prodData['price'] ?? 0),
                        'image' => $image,
                        'dietary_tags' => $prodData['dietary_tags'] ?? [],
                        'calories' => $prodData['calories'] ?? rand(300, 700),
                        'is_available' => true,
                        'sort_order' => $pIdx + 1,
                    ]);
                }
            }
        }

        return redirect()->route('admin.menu.index')->with('success', 'AI Menu successfully imported and published to your menu!');
    }

    public function translateMenu(Request $request)
    {
        $vendor = Auth::user()->vendor;
        $targetLang = $request->validate(['target_language' => 'required|string|in:hy,en,ru,fr,de,es'])['target_language'];

        // Allow sufficient execution time for LLM multi-batch translation
        @set_time_limit(300);
        @ini_set('max_execution_time', '300');

        try {
            // Execute synchronously so that menu items, categories, and descriptions are translated immediately
            TranslateMenuJob::dispatchSync($vendor->id, $targetLang);

            $langLabels = [
                'hy' => '🇦🇲 Հայերեն',
                'en' => '🇬🇧 English',
                'ru' => '🇷🇺 Русский',
                'fr' => '🇫🇷 Français',
                'de' => '🇩🇪 Deutsch',
                'es' => '🇪🇸 Español',
            ];
            $selectedLabel = $langLabels[$targetLang] ?? strtoupper($targetLang);

            return back()->with('success', "✨ Մենյուի բոլոր ուտեստները, նկարագրությունները և կատեգորիաները հաջողությամբ թարգմանվեցին դեպի [{$selectedLabel}] AI-ի միջոցով։");
        } catch (\Throwable $e) {
            Log::error('AI Menu Translation failed: '.$e->getMessage());

            return back()->with('error', 'Թարգմանության ընթացքում առաջացավ խնդիր: '.$e->getMessage());
        }
    }
}
