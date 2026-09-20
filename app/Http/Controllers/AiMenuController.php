<?php

namespace App\Http\Controllers;

use App\Jobs\TranslateMenuJob;
use App\Models\Category;
use App\Models\Product;
use App\Services\AiMenuService;
use App\Services\MenuExtractorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            if ($request->filled('website_url')) {
                $rawText = $this->extractorService->extractFromUrl($request->input('website_url'));
                $parsedMenu = $this->aiService->parseMenuFromText($rawText);
            } elseif ($request->hasFile('menu_file')) {
                $file = $request->file('menu_file');
                $extraction = $this->extractorService->extractFromFile($file);

                if ($extraction['type'] === 'image') {
                    $parsedMenu = $this->aiService->parseMenuFromImage($extraction['base64'], $extraction['mime']);
                } elseif ($extraction['type'] === 'pdf') {
                    $parsedMenu = $this->aiService->parseMenuFromPdf($extraction['base64'], $extraction['text'] ?? null);
                } else {
                    $parsedMenu = $this->aiService->parseMenuFromText($extraction['text'] ?? '');
                }
            } else {
                $parsedMenu = $this->aiService->parseMenuFromText($request->input('menu_text', ''));
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

                    Product::create([
                        'vendor_id' => $vendor->id,
                        'category_id' => $category->id,
                        'name' => $prodData['name'],
                        'name_translations' => ['hy' => $prodData['name'], 'en' => $prodData['name'], 'ru' => $prodData['name']],
                        'description' => $prodData['description'] ?? '',
                        'price' => (float) ($prodData['price'] ?? 0),
                        'image' => 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=600&q=80',
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

        TranslateMenuJob::dispatch($vendor->id, $targetLang);

        return back()->with('success', "✨ Մենյուի թարգմանությունը դեպի [{$targetLang}] մեկնարկեց ֆոնային ռեժիմում։ Խնդրում ենք թարմացնել էջը մի քանի վայրկյանից։");
    }
}
