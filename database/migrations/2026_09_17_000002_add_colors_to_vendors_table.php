<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('accent_color', 20)->nullable()->after('secondary_color');
            $table->string('text_color', 20)->nullable()->after('accent_color');
            $table->string('bg_color', 20)->nullable()->after('text_color');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['accent_color', 'text_color', 'bg_color']);
        });
    }
};
