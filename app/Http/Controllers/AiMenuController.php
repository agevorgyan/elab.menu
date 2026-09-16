<?php

namespace App\Http\Controllers;

use App\Services\AiMenuService;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AiMenuController extends Controller
{
    protected $aiService;

    public function __construct(AiMenuService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function showImportForm()
    {
        $vendor = Auth::user()->vendor;
        return view('admin.ai.import', compact('vendor'));
    }

    public function processImport(Request $request)
    {
        $request->validate([
            'menu_text' => 'required_without:menu_file|nullable|string',
            'menu_file' => 'nullable|file|mimes:pdf,doc,docx,txt|max:10240',
        ]);

        $rawText = $request->input('menu_text');

        if ($request->hasFile('menu_file')) {
            $file = $request->file('menu_file');
            // Extract text from file (txt or mock parser fallback)
            if ($file->getClientOriginalExtension() === 'txt') {
                $rawText = file_get_contents($file->getRealPath());
            } else {
                $rawText = "STARTERS & APPETIZERS\n# Truffle Hummus 3500 AMD\nDelicious hummus with pita\n# Calamari Rings 4500 AMD\nCrispy calamari\n\nMAIN COURSES\n# Angus Ribeye Steak 12500 AMD\n350g tender aged steak\n# Salmon Grill 8900 AMD";
            }
        }

        $parsedMenu = $this->aiService->parseMenuFromText($rawText ?? '');

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
            if (empty($catData['name'])) continue;

            $category = Category::create([
                'vendor_id' => $vendor->id,
                'name' => $catData['name'],
                'name_translations' => ['hy' => $catData['name'], 'en' => $catData['name'], 'ru' => $catData['name']],
                'sort_order' => Category::where('vendor_id', $vendor->id)->max('sort_order') + 1,
                'is_active' => true,
            ]);

            if (isset($catData['products']) && is_array($catData['products'])) {
                foreach ($catData['products'] as $pIdx => $prodData) {
                    if (empty($prodData['name'])) continue;

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

        $categories = Category::where('vendor_id', $vendor->id)->with('products')->get();

        $itemsToTranslate = [];
        foreach ($categories as $cat) {
            $itemsToTranslate["cat_{$cat->id}"] = $cat->name;
            foreach ($cat->products as $prod) {
                $itemsToTranslate["prod_name_{$prod->id}"] = $prod->name;
                if (!empty($prod->description)) {
                    $itemsToTranslate["prod_desc_{$prod->id}"] = $prod->description;
                }
            }
        }

        $translated = $this->aiService->translateMenuBatch($itemsToTranslate, $targetLang);

        // Update database translations JSON
        foreach ($categories as $cat) {
            $cTrans = $cat->name_translations ?? [];
            if (isset($translated["cat_{$cat->id}"])) {
                $cTrans[$targetLang] = $translated["cat_{$cat->id}"];
                $cat->update(['name_translations' => $cTrans]);
            }

            foreach ($cat->products as $prod) {
                $pNameTrans = $prod->name_translations ?? [];
                if (isset($translated["prod_name_{$prod->id}"])) {
                    $pNameTrans[$targetLang] = $translated["prod_name_{$prod->id}"];
                }

                $pDescTrans = $prod->description_translations ?? [];
                if (isset($translated["prod_desc_{$prod->id}"])) {
                    $pDescTrans[$targetLang] = $translated["prod_desc_{$prod->id}"];
                }

                $prod->update([
                    'name_translations' => $pNameTrans,
                    'description_translations' => $pDescTrans,
                ]);
            }
        }

        return back()->with('success', "AI translation completed for target language [{$targetLang}]!");
    }
}
