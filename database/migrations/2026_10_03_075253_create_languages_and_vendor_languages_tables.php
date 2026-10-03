<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Centralized System Languages
        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name', 100);
            $table->string('native_name', 100);
            $table->string('flag', 20)->default('🌐');
            $table->string('direction', 3)->default('ltr'); // ltr or rtl
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_default')->default(false)->index();
            $table->integer('sort_order')->default(0)->index();
            $table->timestamps();
        });

        // 2. Tenant/Restaurant Specific Language Configuration
        Schema::create('vendor_languages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->foreignId('language_id')->constrained('languages')->cascadeOnDelete();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['vendor_id', 'language_id']);
            $table->index(['vendor_id', 'is_active']);
        });

        // 3. Seed Base Global Languages
        $now = now();
        $baseLanguages = [
            ['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'flag' => '🇬🇧', 'direction' => 'ltr', 'is_active' => true, 'is_default' => true, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'hy', 'name' => 'Armenian', 'native_name' => 'Հայերեն', 'flag' => '🇦🇲', 'direction' => 'ltr', 'is_active' => true, 'is_default' => false, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'ru', 'name' => 'Russian', 'native_name' => 'Русский', 'flag' => '🇷🇺', 'direction' => 'ltr', 'is_active' => true, 'is_default' => false, 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'fr', 'name' => 'French', 'native_name' => 'Français', 'flag' => '🇫🇷', 'direction' => 'ltr', 'is_active' => true, 'is_default' => false, 'sort_order' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'de', 'name' => 'German', 'native_name' => 'Deutsch', 'flag' => '🇩🇪', 'direction' => 'ltr', 'is_active' => true, 'is_default' => false, 'sort_order' => 5, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'es', 'name' => 'Spanish', 'native_name' => 'Español', 'flag' => '🇪🇸', 'direction' => 'ltr', 'is_active' => true, 'is_default' => false, 'sort_order' => 6, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'it', 'name' => 'Italian', 'native_name' => 'Italiano', 'flag' => '🇮🇹', 'direction' => 'ltr', 'is_active' => true, 'is_default' => false, 'sort_order' => 7, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'ka', 'name' => 'Georgian', 'native_name' => 'ქართული', 'flag' => '🇬🇪', 'direction' => 'ltr', 'is_active' => true, 'is_default' => false, 'sort_order' => 8, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'ar', 'name' => 'Arabic', 'native_name' => 'العربية', 'flag' => '🇦🇪', 'direction' => 'rtl', 'is_active' => true, 'is_default' => false, 'sort_order' => 9, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'zh', 'name' => 'Chinese', 'native_name' => '中文', 'flag' => '🇨🇳', 'direction' => 'ltr', 'is_active' => true, 'is_default' => false, 'sort_order' => 10, 'created_at' => $now, 'updated_at' => $now],
        ];

        DB::table('languages')->insert($baseLanguages);

        // 4. Backfill existing vendors to associate them with default languages
        $languageMap = DB::table('languages')->pluck('id', 'code')->toArray();
        $vendors = DB::table('vendors')->select('id', 'supported_languages')->get();

        foreach ($vendors as $vendor) {
            $supported = null;
            if (! empty($vendor->supported_languages)) {
                $decoded = json_decode($vendor->supported_languages, true);
                if (is_array($decoded)) {
                    $supported = $decoded;
                }
            }

            if (! empty($supported)) {
                $order = 1;
                foreach ($supported as $langData) {
                    $code = is_array($langData) ? strtolower($langData['code'] ?? '') : strtolower((string) $langData);
                    if (isset($languageMap[$code])) {
                        DB::table('vendor_languages')->insertOrIgnore([
                            'vendor_id' => $vendor->id,
                            'language_id' => $languageMap[$code],
                            'is_default' => ($order === 1),
                            'is_active' => true,
                            'sort_order' => $order++,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            } else {
                // Default to English, Armenian, Russian for existing vendors
                $defaults = ['en', 'hy', 'ru'];
                $order = 1;
                foreach ($defaults as $code) {
                    if (isset($languageMap[$code])) {
                        DB::table('vendor_languages')->insertOrIgnore([
                            'vendor_id' => $vendor->id,
                            'language_id' => $languageMap[$code],
                            'is_default' => ($code === 'en'),
                            'is_active' => true,
                            'sort_order' => $order++,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_languages');
        Schema::dropIfExists('languages');
    }
};
