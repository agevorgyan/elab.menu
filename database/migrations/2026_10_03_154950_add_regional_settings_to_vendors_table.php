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
            if (! Schema::hasColumn('vendors', 'timezone')) {
                $table->string('timezone', 50)->default('Asia/Yerevan')->after('email');
            }
            if (! Schema::hasColumn('vendors', 'weight_unit')) {
                $table->string('weight_unit', 20)->default('g')->after('currency');
            }
            if (! Schema::hasColumn('vendors', 'volume_unit')) {
                $table->string('volume_unit', 20)->default('ml')->after('weight_unit');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $drop = [];
            if (Schema::hasColumn('vendors', 'timezone')) {
                $drop[] = 'timezone';
            }
            if (Schema::hasColumn('vendors', 'weight_unit')) {
                $drop[] = 'weight_unit';
            }
            if (Schema::hasColumn('vendors', 'volume_unit')) {
                $drop[] = 'volume_unit';
            }
            if (! empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};
