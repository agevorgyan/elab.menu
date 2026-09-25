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
        Schema::table('vendors', function (Blueprint $table) {
            $table->json('telegram_settings')->nullable()->after('floor_plan_data');
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->string('telegram_chat_id')->nullable()->after('whatsapp_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn('telegram_chat_id');
        });

        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn('telegram_settings');
        });
    }
};
