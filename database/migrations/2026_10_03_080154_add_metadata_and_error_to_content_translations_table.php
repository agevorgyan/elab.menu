<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('content_translations', function (Blueprint $table) {
            $table->text('error_message')->nullable()->after('is_outdated');
            $table->json('metadata')->nullable()->after('error_message');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('content_translations', function (Blueprint $table) {
            $table->dropColumn(['error_message', 'metadata']);
        });
    }
};
