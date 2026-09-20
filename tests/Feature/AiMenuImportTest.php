<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use ZipArchive;

class AiMenuImportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Vendor $vendor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendor = Vendor::create([
            'name' => 'Armenian Grill House',
            'slug' => 'armenian-grill-house',
            'email' => 'grill@example.com',
            'password' => bcrypt('password'),
            'currency' => 'AMD',
        ]);

        $this->user = User::create([
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'password' => bcrypt('password'),
            'vendor_id' => $this->vendor->id,
            'role' => 'vendor_owner',
        ]);
    }

    public function test_user_can_access_ai_import_page(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.ai.import'));

        $response->assertStatus(200);
        $response->assertSee('AI Խելացի Ներմուծում');
        $response->assertSee('Excel (.xlsx, .xls)');
        $response->assertSee('CSV (.csv)');
        $response->assertSee('Կայքի Հղում');
    }

    public function test_csv_file_import_extracts_and_previews_dishes(): void
    {
        $csvContent = "Category,Product,Price,Description\n"
            ."Starters,Hummus with Truffle,3500,Creamy chickpea puree with pita\n"
            ."Mains,Ribeye Steak,12500,Charcoal grilled beef steak\n"
            ."Drinks,Pomegranate Fresh,1800,Freshly squeezed pomegranate juice\n";

        $file = UploadedFile::fake()->createWithContent('menu.csv', $csvContent);

        $response = $this->actingAs($this->user)->post(route('admin.ai.import.process'), [
            'import_source' => 'file',
            'menu_file' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertViewIs('admin.ai.preview_import');
        $response->assertSee('Hummus with Truffle');
        $response->assertSee('3500');
        $response->assertSee('Ribeye Steak');
        $response->assertSee('12500');
    }

    public function test_docx_file_import_extracts_and_previews_dishes(): void
    {
        // Build a minimal valid DOCX zip archive in memory
        $tempPath = tempnam(sys_get_temp_dir(), 'docx_test');
        $zip = new ZipArchive;
        $zip->open($tempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $documentXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            .'<w:body>'
            .'<w:p><w:r><w:t># STARTERS</w:t></w:r></w:p>'
            .'<w:p><w:r><w:t>Crispy Calamari - 4500 AMD</w:t></w:r></w:p>'
            .'<w:p><w:r><w:t># MAINS</w:t></w:r></w:p>'
            .'<w:p><w:r><w:t>Grilled Salmon Steak - 8900 AMD</w:t></w:r></w:p>'
            .'</w:body></w:document>';
        $zip->addFromString('word/document.xml', $documentXml);
        $zip->close();

        $file = new UploadedFile($tempPath, 'sample_menu.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);

        $response = $this->actingAs($this->user)->post(route('admin.ai.import.process'), [
            'import_source' => 'file',
            'menu_file' => $file,
        ]);

        @unlink($tempPath);

        $response->assertStatus(200);
        $response->assertViewIs('admin.ai.preview_import');
        $response->assertSee('Crispy Calamari');
        $response->assertSee('4500');
        $response->assertSee('Grilled Salmon Steak');
        $response->assertSee('8900');
    }

    public function test_xlsx_file_import_extracts_and_previews_dishes(): void
    {
        // Build a minimal valid XLSX zip archive in memory
        $tempPath = tempnam(sys_get_temp_dir(), 'xlsx_test');
        $zip = new ZipArchive;
        $zip->open($tempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $sharedStringsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="4" uniqueCount="4">'
            .'<si><t>Signature Burger</t></si>'
            .'<si><t>Juicy wagyu beef with cheddar</t></si>'
            .'<si><t>Truffle Fries</t></si>'
            .'<si><t>Crispy fries with parmesan</t></si>'
            .'</sst>';

        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetData>'
            .'<row r="1">'
            .'<c r="A1" t="s"><v>0</v></c>'
            .'<c r="B1"><v>5500</v></c>'
            .'<c r="C1" t="s"><v>1</v></c>'
            .'</row>'
            .'<row r="2">'
            .'<c r="A2" t="s"><v>2</v></c>'
            .'<c r="B2"><v>2200</v></c>'
            .'<c r="C2" t="s"><v>3</v></c>'
            .'</row>'
            .'</sheetData>'
            .'</worksheet>';

        $zip->addFromString('xl/sharedStrings.xml', $sharedStringsXml);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->close();

        $file = new UploadedFile($tempPath, 'dishes.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->actingAs($this->user)->post(route('admin.ai.import.process'), [
            'import_source' => 'file',
            'menu_file' => $file,
        ]);

        @unlink($tempPath);

        $response->assertStatus(200);
        $response->assertViewIs('admin.ai.preview_import');
        $response->assertSee('Signature Burger');
        $response->assertSee('5500');
    }

    public function test_xlsx_arbitrary_columns_extracts_correctly(): void
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'xlsx_arbitrary_test');
        $zip = new ZipArchive;
        $zip->open($tempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $sharedStrings = [
            'Price', 'Images', 'Categories', 'Description', 'Name', // 0-4
            'https://example.com/salmon.jpg', // 5
            'Seafood', // 6
            'Grilled salmon steak with asparagus', // 7
            'Norwegian Salmon', // 8
        ];

        $sstXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="'.count($sharedStrings).'" uniqueCount="'.count($sharedStrings).'">';
        foreach ($sharedStrings as $str) {
            $sstXml .= '<si><t>'.htmlspecialchars($str).'</t></si>';
        }
        $sstXml .= '</sst>';

        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetData>'
            .'<row r="1">'
            .'<c r="A1" t="s"><v>0</v></c>'
            .'<c r="B1" t="s"><v>1</v></c>'
            .'<c r="C1" t="s"><v>2</v></c>'
            .'<c r="D1" t="s"><v>3</v></c>'
            .'<c r="E1" t="s"><v>4</v></c>'
            .'</row>'
            .'<row r="2">'
            .'<c r="A2"><v>7800</v></c>'
            .'<c r="B2" t="s"><v>5</v></c>'
            .'<c r="C2" t="s"><v>6</v></c>'
            .'<c r="D2" t="s"><v>7</v></c>'
            .'<c r="E2" t="s"><v>8</v></c>'
            .'</row>'
            .'</sheetData>'
            .'</worksheet>';

        $zip->addFromString('xl/sharedStrings.xml', $sstXml);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->close();

        $file = new UploadedFile($tempPath, 'arbitrary_menu.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->actingAs($this->user)->post(route('admin.ai.import.process'), [
            'import_source' => 'file',
            'menu_file' => $file,
        ]);

        @unlink($tempPath);

        $response->assertStatus(200);
        $response->assertViewIs('admin.ai.preview_import');
        $response->assertSee('Norwegian Salmon');
        $response->assertSee('7800');
        $response->assertSee('Seafood');
        $response->assertSee('Grilled salmon steak with asparagus');
        $response->assertSee('https://example.com/salmon.jpg');
    }

    public function test_image_file_import_extracts_dishes(): void
    {
        $imageFile = UploadedFile::fake()->image('menu_photo.jpg', 800, 600);

        $response = $this->actingAs($this->user)->post(route('admin.ai.import.process'), [
            'import_source' => 'file',
            'menu_file' => $imageFile,
        ]);

        $response->assertStatus(200);
        $response->assertViewIs('admin.ai.preview_import');
    }

    public function test_website_url_import_fetches_and_extracts_dishes(): void
    {
        $fakeHtml = '<html><head><title>Bistro Menu</title></head><body>'
            .'<h1>Gourmet Cafe</h1>'
            .'<h2>APPETIZERS</h2>'
            .'<div class="dish-item">Avocado Bruschetta - 3200 AMD: Fresh sourdough with avocado</div>'
            .'<h2>PIZZA & PASTA</h2>'
            .'<div class="dish-item">Pizza Margherita - 4200 AMD: Fresh mozzarella and basil</div>'
            .'</body></html>';

        Http::fake([
            'https://bistro-example.com/menu' => Http::response($fakeHtml, 200),
        ]);

        $response = $this->actingAs($this->user)->post(route('admin.ai.import.process'), [
            'import_source' => 'url',
            'website_url' => 'https://bistro-example.com/menu',
        ]);

        $response->assertStatus(200);
        $response->assertViewIs('admin.ai.preview_import');
        $response->assertSee('Avocado Bruschetta');
        $response->assertSee('3200');
        $response->assertSee('Pizza Margherita');
        $response->assertSee('4200');
    }

    public function test_website_url_import_fetches_woocommerce_multi_page_catalog(): void
    {
        $page1Html = '<html><body>'
            .'<div class="product-small col product type-product">'
            .'  <div class="box-image"><img src="data:image/svg+xml,%3Csvg" data-src="https://example.com/amaretto.jpg" /></div>'
            .'  <div class="box-text">'
            .'    <p class="category product-cat">Կոկտեյլներ</p>'
            .'    <p class="name product-title"><a href="https://example.com/product/amaretto/">Amaretto sour</a></p>'
            .'    <span class="price"><span class="amount"><bdi>3 000&nbsp;AMD</bdi></span></span>'
            .'  </div>'
            .'</div>'
            .'<nav class="woocommerce-pagination"><a class="page-number" href="https://example.com/products/page/2/">2</a></nav>'
            .'</body></html>';

        $page2Html = '<html><body>'
            .'<div class="product-small col product type-product">'
            .'  <div class="box-image"><img src="data:image/svg+xml,%3Csvg" data-src="https://example.com/breakfast.jpg" /></div>'
            .'  <div class="box-text">'
            .'    <p class="category product-cat">Նախաճաշ</p>'
            .'    <p class="name product-title"><a href="https://example.com/product/breakfast/">Անգլիական Նախաճաշ</a></p>'
            .'    <span class="price"><span class="amount"><bdi>3 200&nbsp;AMD</bdi></span></span>'
            .'  </div>'
            .'</div>'
            .'</body></html>';

        Http::fake([
            'https://example.com/products/' => Http::response($page1Html, 200),
            'https://example.com/products/page/2/' => Http::response($page2Html, 200),
        ]);

        $response = $this->actingAs($this->user)->post(route('admin.ai.import.process'), [
            'import_source' => 'url',
            'website_url' => 'https://example.com/products/',
        ]);

        $response->assertStatus(200);
        $response->assertViewIs('admin.ai.preview_import');
        $response->assertSee('Կոկտեյլներ');
        $response->assertSee('Amaretto sour');
        $response->assertSee('3000');
        $response->assertSee('https://example.com/amaretto.jpg');
        $response->assertSee('Նախաճաշ');
        $response->assertSee('Անգլիական Նախաճաշ');
        $response->assertSee('3200');
        $response->assertSee('https://example.com/breakfast.jpg');
    }

    public function test_confirm_import_persists_categories_and_products_to_database(): void
    {
        $payload = [
            'categories' => [
                [
                    'name' => 'Hot Appetizers',
                    'products' => [
                        [
                            'name' => 'Stuffed Mushrooms',
                            'description' => 'Baked with herbs and cheese',
                            'price' => 2800,
                            'dietary_tags' => ['vegetarian'],
                            'calories' => 320,
                        ],
                        [
                            'name' => 'Crispy Chicken Wings',
                            'description' => 'Spicy glazed wings with ranch dip',
                            'price' => 3900,
                            'dietary_tags' => ['spicy'],
                            'calories' => 540,
                        ],
                    ],
                ],
                [
                    'name' => 'Desserts',
                    'products' => [
                        [
                            'name' => 'San Sebastian Cheesecake',
                            'description' => 'Caramelized burnt cheesecake with berries',
                            'price' => 2500,
                            'dietary_tags' => ['vegetarian'],
                            'calories' => 450,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('admin.ai.import.confirm'), $payload);

        $response->assertRedirect(route('admin.menu.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('categories', [
            'vendor_id' => $this->vendor->id,
            'name' => 'Hot Appetizers',
        ]);

        $this->assertDatabaseHas('categories', [
            'vendor_id' => $this->vendor->id,
            'name' => 'Desserts',
        ]);

        $this->assertDatabaseHas('products', [
            'vendor_id' => $this->vendor->id,
            'name' => 'Stuffed Mushrooms',
            'price' => 2800,
        ]);

        $this->assertDatabaseHas('products', [
            'vendor_id' => $this->vendor->id,
            'name' => 'San Sebastian Cheesecake',
            'price' => 2500,
        ]);
    }

    public function test_empty_import_submission_returns_error(): void
    {
        $response = $this->actingAs($this->user)->post(route('admin.ai.import.process'), []);

        $response->assertSessionHas('error');
    }

    public function test_invalid_website_url_returns_graceful_error(): void
    {
        Http::fake([
            'https://nonexistent-broken-menu-site.am' => Http::response('Not found', 404),
        ]);

        $response = $this->actingAs($this->user)->post(route('admin.ai.import.process'), [
            'import_source' => 'url',
            'website_url' => 'https://nonexistent-broken-menu-site.am',
        ]);

        $response->assertSessionHas('error');
    }

    public function test_arbitrary_column_ordering_csv_extracts_correct_fields(): void
    {
        // Columns in arbitrary order: Price, Images, Categories, Description, Name
        $csvContent = "Price,Images,Categories,Description,Name\n"
            ."4500,https://example.com/pizza.jpg,Pizza,Wood-fired margherita,Pizza Margherita\n"
            ."1800,https://example.com/cola.jpg,Drinks,Cold beverage,Craft Cola\n";

        $file = UploadedFile::fake()->createWithContent('arbitrary_order.csv', $csvContent);

        $response = $this->actingAs($this->user)->post(route('admin.ai.import.process'), [
            'import_source' => 'file',
            'menu_file' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertViewIs('admin.ai.preview_import');
        $response->assertSee('Pizza Margherita');
        $response->assertSee('4500');
        $response->assertSee('Pizza');
        $response->assertSee('Wood-fired margherita');
        $response->assertSee('https://example.com/pizza.jpg');
        $response->assertSee('Craft Cola');
        $response->assertSee('1800');
        $response->assertSee('Drinks');
    }

    public function test_armenian_headers_csv_arbitrary_order_extracts_correctly(): void
    {
        // Armenian column headers in arbitrary order: Ապրանքախումբ, Նկարներ, Գին, Անվանում, Նկարագրություն
        $csvContent = "Ապրանքախումբ,Նկարներ,Գին,Անվանում,Նկարագրություն\n"
            ."Խորոված,https://example.com/khorovats.jpg,6500,Խոզի Խորոված,Թարմ մսով պատրաստված\n"
            ."Աղցաններ,https://example.com/salad.jpg,2800,Հունական Աղցան,Ֆետա պանրով և ձիթապտղով\n";

        $file = UploadedFile::fake()->createWithContent('armenian_menu.csv', $csvContent);

        $response = $this->actingAs($this->user)->post(route('admin.ai.import.process'), [
            'import_source' => 'file',
            'menu_file' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertViewIs('admin.ai.preview_import');
        $response->assertSee('Խոզի Խորոված');
        $response->assertSee('6500');
        $response->assertSee('Խորոված');
        $response->assertSee('Թարմ մսով պատրաստված');
        $response->assertSee('https://example.com/khorovats.jpg');
        $response->assertSee('Հունական Աղցան');
        $response->assertSee('2800');
        $response->assertSee('Աղցաններ');
    }

    public function test_confirm_import_saves_custom_product_image_url(): void
    {
        $payload = [
            'categories' => [
                [
                    'name' => 'Signature Dishes',
                    'products' => [
                        [
                            'name' => 'Truffle Burger',
                            'description' => 'Brioche bun with black truffle sauce',
                            'price' => 5200,
                            'image' => 'https://example.com/truffle-burger.jpg',
                            'dietary_tags' => ['chef_special'],
                            'calories' => 650,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('admin.ai.import.confirm'), $payload);

        $response->assertRedirect(route('admin.menu.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'vendor_id' => $this->vendor->id,
            'name' => 'Truffle Burger',
            'price' => 5200,
            'image' => 'https://example.com/truffle-burger.jpg',
        ]);
    }

    public function test_preview_renders_large_dataset_with_quotes_without_html_breakage(): void
    {
        $csv = "Name,Description,Price,Categories,Images\n";
        for ($i = 1; $i <= 50; $i++) {
            $csv .= "Dish \"Special\" #{$i},Freshly grilled with chef's \"secret\" sauce,3500,Appetizers,https://example.com/dish{$i}.jpg\n";
        }

        $file = UploadedFile::fake()->createWithContent('large_menu.csv', $csv);

        $response = $this->actingAs($this->user)->post(route('admin.ai.import.process'), [
            'import_source' => 'file',
            'menu_file' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertViewIs('admin.ai.preview_import');
        // Ensure no raw attribute spillover leaked
        $response->assertDontSee('} }, addProduct(cIdx)', false);
        // Ensure script container exists
        $response->assertSee('id="parsed-menu-json"', false);
        $response->assertSee('menuImportApp()', false);
    }

    public function test_woocommerce_csv_export_maps_categories_prices_and_images_correctly(): void
    {
        $header = 'ID,Type,SKU,"GTIN, UPC, EAN, or ISBN",Name,Published,"Is featured?","Visibility in catalog","Short description",Description,"Date sale price starts","Date sale price ends","Tax status","Tax class","In stock?",Stock,"Low stock amount","Backorders allowed?","Sold individually?","Weight (kg)","Length (cm)","Width (cm)","Height (cm)","Allow customer reviews?","Purchase note","Sale price","Regular price",Categories,Tags,"Shipping class",Images,"Download limit","Download expiry days",Parent,"Grouped products",Upsells,Cross-sells,"External URL","Button text",Position,Brands';

        $row1 = '29,simple,3,,"Անգլիական Նախաճաշ",1,0,visible,,,,,taxable,,1,,,0,0,,,,,0,,,3200,Նախաճաշ,,,https://parkside.rest/wp-content/uploads/2026/08/breakfast.jpg,,,,,,,,,0,';
        $row2 = '32,simple,6,,"Աղցան Ճակնդեղով և Հորած Պանրով",1,0,visible,,,,,taxable,,1,,,0,0,,,,,0,,,2800,Աղցաններ,,,https://parkside.rest/wp-content/uploads/2026/08/salad.jpg,,,,,,,,,0,';

        $csv = $header."\n".$row1."\n".$row2."\n";

        $file = UploadedFile::fake()->createWithContent('parkside_woo.csv', $csv);

        $response = $this->actingAs($this->user)->post(route('admin.ai.import.process'), [
            'import_source' => 'file',
            'menu_file' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertViewIs('admin.ai.preview_import');

        // Check that categories are correctly extracted (and NOT lumped into 'simple')
        $response->assertSee('Նախաճաշ');
        $response->assertSee('Աղցաններ');
        $response->assertDontSee('"name":"simple"', false);

        // Check dishes and their regular prices
        $response->assertSee('Անգլիական Նախաճաշ');
        $response->assertSee('3200');
        $response->assertSee('Աղցան Ճակնդեղով և Հորած Պանրով');
        $response->assertSee('2800');

        // Check images
        $response->assertSee('breakfast.jpg');
        $response->assertSee('salad.jpg');
    }

    public function test_one_click_menu_translation_executes_and_updates_translations(): void
    {
        $category = Category::create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Նախուտեստներ',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $product = Product::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $category->id,
            'name' => 'Խորոված խոզի',
            'description' => 'Համեղ միս',
            'price' => 3500,
            'is_available' => true,
            'sort_order' => 1,
        ]);

        $this->vendor->update([
            'ai_settings' => [
                'provider' => 'gemini',
                'model' => 'gemini-3.6-flash',
                'api_key' => 'fake-gemini-key',
            ],
        ]);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        's_0' => 'Appetizers',
                                        's_1' => 'Pork BBQ',
                                        's_2' => 'Delicious meat',
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->user)->post(route('admin.ai.translate'), [
            'target_language' => 'en',
        ]);

        $response->assertSessionHas('success');
        $response->assertRedirect();

        $category->refresh();
        $product->refresh();

        $this->assertEquals('Appetizers', $category->name_translations['en'] ?? null);
        $this->assertEquals('Pork BBQ', $product->name_translations['en'] ?? null);
        $this->assertEquals('Delicious meat', $product->description_translations['en'] ?? null);
    }
}
