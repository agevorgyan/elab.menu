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
            $table->boolean('dine_in_schedule_enabled')->default(false)->after('working_hours');
            $table->string('dine_in_start_time', 10)->nullable()->after('dine_in_schedule_enabled');
            $table->string('dine_in_end_time', 10)->nullable()->after('dine_in_start_time');
            $table->json('dine_in_days')->nullable()->after('dine_in_end_time');

            $table->boolean('delivery_schedule_enabled')->default(false)->after('delivery_free_from');
            $table->string('delivery_start_time', 10)->nullable()->after('delivery_schedule_enabled');
            $table->string('delivery_end_time', 10)->nullable()->after('delivery_start_time');
            $table->json('delivery_days')->nullable()->after('delivery_end_time');

            $table->boolean('takeaway_schedule_enabled')->default(false)->after('takeaway_min_amount');
            $table->string('takeaway_start_time', 10)->nullable()->after('takeaway_schedule_enabled');
            $table->string('takeaway_end_time', 10)->nullable()->after('takeaway_start_time');
            $table->json('takeaway_days')->nullable()->after('takeaway_end_time');

            $table->boolean('closing_warning_enabled')->default(true)->after('takeaway_days');
            $table->unsignedInteger('closing_warning_minutes')->default(30)->after('closing_warning_enabled');
            $table->text('closing_warning_message')->nullable()->after('closing_warning_minutes');
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->boolean('dine_in_schedule_enabled')->nullable()->after('working_hours');
            $table->string('dine_in_start_time', 10)->nullable()->after('dine_in_schedule_enabled');
            $table->string('dine_in_end_time', 10)->nullable()->after('dine_in_start_time');
            $table->json('dine_in_days')->nullable()->after('dine_in_end_time');

            $table->boolean('delivery_schedule_enabled')->nullable()->after('dine_in_days');
            $table->string('delivery_start_time', 10)->nullable()->after('delivery_schedule_enabled');
            $table->string('delivery_end_time', 10)->nullable()->after('delivery_start_time');
            $table->json('delivery_days')->nullable()->after('delivery_end_time');

            $table->boolean('takeaway_schedule_enabled')->nullable()->after('delivery_days');
            $table->string('takeaway_start_time', 10)->nullable()->after('takeaway_schedule_enabled');
            $table->string('takeaway_end_time', 10)->nullable()->after('takeaway_start_time');
            $table->json('takeaway_days')->nullable()->after('takeaway_end_time');

            $table->boolean('closing_warning_enabled')->nullable()->after('takeaway_days');
            $table->unsignedInteger('closing_warning_minutes')->nullable()->after('closing_warning_enabled');
            $table->text('closing_warning_message')->nullable()->after('closing_warning_minutes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $columns = [
            'dine_in_schedule_enabled',
            'dine_in_start_time',
            'dine_in_end_time',
            'dine_in_days',
            'delivery_schedule_enabled',
            'delivery_start_time',
            'delivery_end_time',
            'delivery_days',
            'takeaway_schedule_enabled',
            'takeaway_start_time',
            'takeaway_end_time',
            'takeaway_days',
            'closing_warning_enabled',
            'closing_warning_minutes',
            'closing_warning_message',
        ];

        Schema::table('vendors', function (Blueprint $table) use ($columns) {
            $table->dropColumn($columns);
        });

        Schema::table('locations', function (Blueprint $table) use ($columns) {
            $table->dropColumn($columns);
        });
    }
};
