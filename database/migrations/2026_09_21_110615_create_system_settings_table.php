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
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->index();
            $table->text('value')->nullable();
            $table->string('group')->default('general');
            $table->timestamps();
        });

        // Insert initial platform contact & configuration settings
        $defaults = [
            ['key' => 'contact_phone', 'value' => '+37455776066', 'group' => 'contact'],
            ['key' => 'contact_whatsapp', 'value' => '+37455776066', 'group' => 'contact'],
            ['key' => 'contact_telegram', 'value' => '+37455776066', 'group' => 'contact'],
            ['key' => 'contact_email', 'value' => 'menu@elab.am', 'group' => 'contact'],
            ['key' => 'social_facebook', 'value' => 'https://facebook.com/elab.menu', 'group' => 'social'],
            ['key' => 'social_instagram', 'value' => 'https://instagram.com/elab.menu', 'group' => 'social'],
            ['key' => 'demo_vendor_slug', 'value' => 'bistro-yerevan', 'group' => 'landing'],
            ['key' => 'trial_days', 'value' => '14', 'group' => 'landing'],
        ];

        foreach ($defaults as $setting) {
            DB::table('system_settings')->insert(array_merge($setting, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
