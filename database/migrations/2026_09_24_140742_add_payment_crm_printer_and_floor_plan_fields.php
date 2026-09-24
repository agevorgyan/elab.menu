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
            $table->json('payment_settings')->nullable()->after('allow_whatsapp_orders');
            $table->json('crm_settings')->nullable()->after('payment_settings');
            $table->json('thermal_printer_settings')->nullable()->after('crm_settings');
            $table->json('floor_plan_data')->nullable()->after('thermal_printer_settings');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_method')->default('cash')->after('type');
            $table->string('payment_status')->default('unpaid')->after('payment_method');
            $table->string('payment_transaction_id')->nullable()->after('payment_status');
            $table->decimal('birthday_discount_amount', 10, 2)->default(0)->after('delivery_fee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'payment_status', 'payment_transaction_id', 'birthday_discount_amount']);
        });

        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['payment_settings', 'crm_settings', 'thermal_printer_settings', 'floor_plan_data']);
        });
    }
};
