<?php

namespace Tests\Feature;

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
}
