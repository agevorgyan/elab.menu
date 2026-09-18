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
        Schema::table('customers', function (Blueprint $table) {
            $table->date('birthdate')->nullable()->after('email');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->date('customer_birthdate')->nullable()->after('customer_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('birthdate');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('customer_birthdate');
        });
    }
};
