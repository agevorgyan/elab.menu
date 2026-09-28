<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();
        if ($driver !== 'pgsql') {
            return;
        }

        // 1. Enable pg_trgm extension for high-performance fuzzy & trigram search
        try {
            DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm;');
        } catch (Throwable $e) {
            // Extension might require superuser or already exist
        }

        // 2. Trigram GIN indexes for fast case-insensitive search
        $trgmIndexes = [
            'idx_products_name_trgm' => 'CREATE INDEX IF NOT EXISTS idx_products_name_trgm ON products USING gin (name gin_trgm_ops);',
            'idx_customers_name_trgm' => 'CREATE INDEX IF NOT EXISTS idx_customers_name_trgm ON customers USING gin (name gin_trgm_ops);',
            'idx_categories_name_trgm' => 'CREATE INDEX IF NOT EXISTS idx_categories_name_trgm ON categories USING gin (name gin_trgm_ops);',
        ];

        foreach ($trgmIndexes as $name => $sql) {
            try {
                DB::statement($sql);
            } catch (Throwable $e) {
                // Ignore if index creation fails on unprivileged environments
            }
        }

        // 3. Functional Expression Indexes for Case-Insensitive Lookups
        $expressionIndexes = [
            'idx_users_email_lower' => 'CREATE INDEX IF NOT EXISTS idx_users_email_lower ON users (LOWER(email));',
            'idx_vendors_slug_lower' => 'CREATE INDEX IF NOT EXISTS idx_vendors_slug_lower ON vendors (LOWER(slug));',
            'idx_locations_slug_lower' => 'CREATE INDEX IF NOT EXISTS idx_locations_slug_lower ON locations (LOWER(slug));',
            'idx_customers_email_lower' => 'CREATE INDEX IF NOT EXISTS idx_customers_email_lower ON customers (LOWER(email));',
        ];

        foreach ($expressionIndexes as $name => $sql) {
            try {
                DB::statement($sql);
            } catch (Throwable $e) {
                // Ignore if index already exists
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver !== 'pgsql') {
            return;
        }

        $indexesToDrop = [
            'idx_products_name_trgm',
            'idx_customers_name_trgm',
            'idx_categories_name_trgm',
            'idx_users_email_lower',
            'idx_vendors_slug_lower',
            'idx_locations_slug_lower',
            'idx_customers_email_lower',
        ];

        foreach ($indexesToDrop as $idx) {
            try {
                DB::statement("DROP INDEX IF EXISTS {$idx};");
            } catch (Throwable $e) {
                // Ignore drop errors
            }
        }
    }
};
