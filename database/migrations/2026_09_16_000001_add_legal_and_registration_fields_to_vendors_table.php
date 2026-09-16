<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('legal_name')->nullable()->after('name');
            $table->string('legal_address')->nullable()->after('legal_name');
            $table->string('tax_id')->nullable()->after('legal_address'); // ՀՎՀՀ
            $table->string('director_name')->nullable()->after('tax_id');
            $table->string('contact_person_name')->nullable()->after('director_name');
            $table->text('operating_address')->nullable()->after('contact_person_name');
            $table->integer('expected_locations_count')->default(1)->after('operating_address');
            $table->timestamp('email_verified_at')->nullable()->after('subscription_plan');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn([
                'legal_name',
                'legal_address',
                'tax_id',
                'director_name',
                'contact_person_name',
                'operating_address',
                'expected_locations_count',
                'email_verified_at',
            ]);
        });
    }
};
