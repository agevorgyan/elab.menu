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
            $table->string('director_phone')->nullable()->after('director_name');
            $table->string('contact_person_phone')->nullable()->after('contact_person_name');
            $table->string('wifi_ssid')->nullable()->after('cover_image');
            $table->string('wifi_password')->nullable()->after('wifi_ssid');
            $table->string('working_hours')->nullable()->after('wifi_password');
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->string('wifi_ssid')->nullable()->after('whatsapp_number');
            $table->string('wifi_password')->nullable()->after('wifi_ssid');
            $table->string('working_hours')->nullable()->after('wifi_password');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn([
                'director_phone',
                'contact_person_phone',
                'wifi_ssid',
                'wifi_password',
                'working_hours',
            ]);
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn([
                'wifi_ssid',
                'wifi_password',
                'working_hours',
            ]);
        });
    }
};
