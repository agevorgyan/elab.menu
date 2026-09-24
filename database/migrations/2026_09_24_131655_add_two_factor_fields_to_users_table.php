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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('two_factor_enabled')->default(false)->after('password');
            $table->string('two_factor_type', 30)->default('email')->after('two_factor_enabled');
            $table->text('two_factor_secret')->nullable()->after('two_factor_type');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_secret');
            $table->string('two_factor_email_code', 10)->nullable()->after('two_factor_confirmed_at');
            $table->timestamp('two_factor_email_expires_at')->nullable()->after('two_factor_email_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'two_factor_enabled',
                'two_factor_type',
                'two_factor_secret',
                'two_factor_confirmed_at',
                'two_factor_email_code',
                'two_factor_email_expires_at',
            ]);
        });
    }
};
