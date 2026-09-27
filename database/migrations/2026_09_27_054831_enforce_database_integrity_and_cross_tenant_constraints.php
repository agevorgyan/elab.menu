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
        // 1. Composite Unique Constraints on Parent Tables (Prerequisite for Composite Foreign Keys)
        $uniques = [
            'locations' => ['columns' => ['id', 'vendor_id'], 'name' => 'locations_id_vendor_id_unique'],
            'categories' => ['columns' => ['id', 'vendor_id'], 'name' => 'categories_id_vendor_id_unique'],
            'products' => ['columns' => ['id', 'vendor_id'], 'name' => 'products_id_vendor_id_unique'],
            'customers' => ['columns' => ['id', 'vendor_id'], 'name' => 'customers_id_vendor_id_unique'],
            'orders' => ['columns' => ['id', 'vendor_id'], 'name' => 'orders_id_vendor_id_unique'],
            'subscription_payments' => ['columns' => ['id', 'vendor_id'], 'name' => 'sub_payments_id_vendor_id_unique'],
            'ai_waiter_sessions' => ['columns' => ['id', 'vendor_id'], 'name' => 'ai_sessions_id_vendor_id_unique'],
        ];

        foreach ($uniques as $table => $cfg) {
            if (Schema::hasTable($table)) {
                try {
                    Schema::table($table, function (Blueprint $t) use ($cfg) {
                        $t->unique($cfg['columns'], $cfg['name']);
                    });
                } catch (Throwable $e) {
                    // Unique constraint may already exist
                }
            }
        }

        // 2. Composite Foreign Keys on Child Tables (Cross-Tenant Relationship Enforcement)
        $fks = [
            'products' => [
                ['columns' => ['category_id', 'vendor_id'], 'name' => 'products_cat_vendor_fk', 'ref_columns' => ['id', 'vendor_id'], 'on' => 'categories', 'action' => 'cascade'],
            ],
            'orders' => [
                ['columns' => ['location_id', 'vendor_id'], 'name' => 'orders_loc_vendor_fk', 'ref_columns' => ['id', 'vendor_id'], 'on' => 'locations', 'action' => 'cascade'],
                ['columns' => ['customer_id', 'vendor_id'], 'name' => 'orders_cust_vendor_fk', 'ref_columns' => ['id', 'vendor_id'], 'on' => 'customers', 'action' => 'cascade'],
            ],
            'categories' => [
                ['columns' => ['location_id', 'vendor_id'], 'name' => 'categories_loc_vendor_fk', 'ref_columns' => ['id', 'vendor_id'], 'on' => 'locations', 'action' => 'cascade'],
            ],
            'location_product_overrides' => [
                ['columns' => ['location_id', 'vendor_id'], 'name' => 'lpo_loc_vendor_fk', 'ref_columns' => ['id', 'vendor_id'], 'on' => 'locations', 'action' => 'cascade'],
                ['columns' => ['product_id', 'vendor_id'], 'name' => 'lpo_prod_vendor_fk', 'ref_columns' => ['id', 'vendor_id'], 'on' => 'products', 'action' => 'cascade'],
            ],
            'waiter_calls' => [
                ['columns' => ['location_id', 'vendor_id'], 'name' => 'waiter_calls_loc_vendor_fk', 'ref_columns' => ['id', 'vendor_id'], 'on' => 'locations', 'action' => 'cascade'],
            ],
            'ai_waiter_sessions' => [
                ['columns' => ['location_id', 'vendor_id'], 'name' => 'ai_sessions_loc_vendor_fk', 'ref_columns' => ['id', 'vendor_id'], 'on' => 'locations', 'action' => 'cascade'],
                ['columns' => ['order_id', 'vendor_id'], 'name' => 'ai_sessions_order_vendor_fk', 'ref_columns' => ['id', 'vendor_id'], 'on' => 'orders', 'action' => 'cascade'],
            ],
            'payment_attempts' => [
                ['columns' => ['order_id', 'vendor_id'], 'name' => 'payment_attempts_order_vendor_fk', 'ref_columns' => ['id', 'vendor_id'], 'on' => 'orders', 'action' => 'cascade'],
                ['columns' => ['subscription_id', 'vendor_id'], 'name' => 'payment_attempts_sub_vendor_fk', 'ref_columns' => ['id', 'vendor_id'], 'on' => 'subscription_payments', 'action' => 'cascade'],
            ],
            'customers' => [
                ['columns' => ['location_id', 'vendor_id'], 'name' => 'customers_loc_vendor_fk', 'ref_columns' => ['id', 'vendor_id'], 'on' => 'locations', 'action' => 'cascade'],
            ],
            'ai_usage_logs' => [
                ['columns' => ['session_id', 'vendor_id'], 'name' => 'ai_logs_session_vendor_fk', 'ref_columns' => ['id', 'vendor_id'], 'on' => 'ai_waiter_sessions', 'action' => 'cascade'],
            ],
            'analytics_logs' => [
                ['columns' => ['location_id', 'vendor_id'], 'name' => 'analytics_logs_loc_vendor_fk', 'ref_columns' => ['id', 'vendor_id'], 'on' => 'locations', 'action' => 'cascade'],
            ],
            'push_subscriptions' => [
                ['columns' => ['location_id', 'vendor_id'], 'name' => 'push_subs_loc_vendor_fk', 'ref_columns' => ['id', 'vendor_id'], 'on' => 'locations', 'action' => 'cascade'],
            ],
            'users' => [
                ['columns' => ['location_id', 'vendor_id'], 'name' => 'users_loc_vendor_fk', 'ref_columns' => ['id', 'vendor_id'], 'on' => 'locations', 'action' => 'cascade'],
            ],
        ];

        foreach ($fks as $table => $tableFks) {
            if (Schema::hasTable($table)) {
                foreach ($tableFks as $fk) {
                    try {
                        Schema::table($table, function (Blueprint $t) use ($fk) {
                            $t->foreign($fk['columns'], $fk['name'])
                                ->references($fk['ref_columns'])
                                ->on($fk['on'])
                                ->cascadeOnDelete();
                        });
                    } catch (Throwable $e) {
                        // FK constraint may already exist from previous partial migration
                    }
                }
            }
        }

        // 3. Composite Indexes for Tenant-First & Soft-Delete Queries
        // 3. Composite Indexes for Tenant-First & Soft-Delete Queries
        $tableIndexes = [
            'categories' => [
                ['columns' => ['vendor_id', 'is_active', 'sort_order'], 'name' => 'idx_cat_vendor_active_sort'],
                ['columns' => ['vendor_id', 'deleted_at'], 'name' => 'idx_cat_vendor_deleted'],
            ],
            'products' => [
                ['columns' => ['vendor_id', 'category_id', 'is_available', 'sort_order'], 'name' => 'idx_prod_vendor_cat_avail_sort'],
                ['columns' => ['vendor_id', 'is_available'], 'name' => 'idx_prod_vendor_available'],
                ['columns' => ['vendor_id', 'is_featured'], 'name' => 'idx_prod_vendor_featured'],
                ['columns' => ['vendor_id', 'deleted_at'], 'name' => 'idx_prod_vendor_deleted'],
            ],
            'orders' => [
                ['columns' => ['vendor_id', 'status', 'created_at'], 'name' => 'idx_orders_vendor_status_created'],
                ['columns' => ['vendor_id', 'location_id', 'status'], 'name' => 'idx_orders_vendor_loc_status'],
                ['columns' => ['vendor_id', 'type'], 'name' => 'idx_orders_vendor_type'],
                ['columns' => ['vendor_id', 'deleted_at'], 'name' => 'idx_orders_vendor_deleted'],
            ],
            'waiter_calls' => [
                ['columns' => ['vendor_id', 'status', 'created_at'], 'name' => 'idx_waiter_calls_vendor_status_created'],
                ['columns' => ['vendor_id', 'location_id', 'status'], 'name' => 'idx_waiter_calls_vendor_loc_status'],
                ['columns' => ['vendor_id', 'table_number', 'status'], 'name' => 'idx_waiter_calls_vendor_table_status'],
            ],
            'customers' => [
                ['columns' => ['vendor_id', 'phone'], 'name' => 'idx_customers_vendor_phone'],
                ['columns' => ['vendor_id', 'email'], 'name' => 'idx_customers_vendor_email'],
            ],
            'ai_waiter_sessions' => [
                ['columns' => ['vendor_id', 'status'], 'name' => 'idx_ai_sessions_vendor_status'],
                ['columns' => ['vendor_id', 'location_id', 'status'], 'name' => 'idx_ai_sessions_vendor_loc_status'],
            ],
            'payment_attempts' => [
                ['columns' => ['vendor_id', 'status'], 'name' => 'idx_payment_attempts_vendor_status'],
                ['columns' => ['vendor_id', 'order_id'], 'name' => 'idx_payment_attempts_vendor_order'],
                ['columns' => ['vendor_id', 'merchant_reference'], 'name' => 'idx_payment_attempts_vendor_ref'],
            ],
            'vendor_storage_files' => [
                ['columns' => ['vendor_id', 'status'], 'name' => 'idx_storage_files_vendor_status'],
                ['columns' => ['vendor_id', 'deleted_at'], 'name' => 'idx_storage_files_vendor_deleted'],
            ],
        ];

        foreach ($tableIndexes as $table => $indexes) {
            if (Schema::hasTable($table)) {
                foreach ($indexes as $idx) {
                    try {
                        Schema::table($table, function (Blueprint $t) use ($idx) {
                            $t->index($idx['columns'], $idx['name']);
                        });
                    } catch (Throwable $e) {
                        // Index may already exist
                    }
                }
            }
        }

        // 4. Check Constraints where supported by database driver (MySQL 8+ and PostgreSQL)
        $driver = DB::getDriverName();
        if ($driver === 'mysql' || $driver === 'pgsql') {
            $checks = [
                'products' => 'ALTER TABLE products ADD CONSTRAINT chk_products_price_non_negative CHECK (price >= 0)',
                'order_items' => 'ALTER TABLE order_items ADD CONSTRAINT chk_order_items_qty_positive CHECK (quantity > 0)',
                'orders' => 'ALTER TABLE orders ADD CONSTRAINT chk_orders_total_non_negative CHECK (total_amount >= 0)',
                'payment_attempts' => 'ALTER TABLE payment_attempts ADD CONSTRAINT chk_payment_attempts_amount_positive CHECK (amount >= 0)',
                'subscription_payments' => 'ALTER TABLE subscription_payments ADD CONSTRAINT chk_sub_payments_amount_positive CHECK (amount >= 0)',
                'location_product_overrides' => 'ALTER TABLE location_product_overrides ADD CONSTRAINT chk_lpo_price_non_negative CHECK (override_price IS NULL OR override_price >= 0)',
            ];

            foreach ($checks as $table => $sql) {
                if (Schema::hasTable($table)) {
                    try {
                        DB::statement($sql);
                    } catch (Throwable $e) {
                        // Check constraint might already exist or driver variant
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Drop Check Constraints where supported
        $driver = DB::getDriverName();
        if ($driver === 'mysql' || $driver === 'pgsql') {
            $checkDrops = [
                'products' => 'ALTER TABLE products DROP CONSTRAINT chk_products_price_non_negative',
                'order_items' => 'ALTER TABLE order_items DROP CONSTRAINT chk_order_items_qty_positive',
                'orders' => 'ALTER TABLE orders DROP CONSTRAINT chk_orders_total_non_negative',
                'payment_attempts' => 'ALTER TABLE payment_attempts DROP CONSTRAINT chk_payment_attempts_amount_positive',
                'subscription_payments' => 'ALTER TABLE subscription_payments DROP CONSTRAINT chk_sub_payments_amount_positive',
                'location_product_overrides' => 'ALTER TABLE location_product_overrides DROP CONSTRAINT chk_lpo_price_non_negative',
            ];

            foreach ($checkDrops as $table => $sql) {
                if (Schema::hasTable($table)) {
                    try {
                        DB::statement($sql);
                    } catch (Throwable $e) {
                        // Constraint might not exist
                    }
                }
            }
        }

        // 2. Drop Composite Indexes safely
        $indexes = [
            'vendor_storage_files' => ['idx_storage_files_vendor_status', 'idx_storage_files_vendor_deleted'],
            'payment_attempts' => ['idx_payment_attempts_vendor_status', 'idx_payment_attempts_vendor_order', 'idx_payment_attempts_vendor_ref'],
            'ai_waiter_sessions' => ['idx_ai_sessions_vendor_status', 'idx_ai_sessions_vendor_loc_status'],
            'customers' => ['idx_customers_vendor_phone', 'idx_customers_vendor_email'],
            'waiter_calls' => ['idx_waiter_calls_vendor_status_created', 'idx_waiter_calls_vendor_loc_status', 'idx_waiter_calls_vendor_table_status'],
            'orders' => ['idx_orders_vendor_status_created', 'idx_orders_vendor_loc_status', 'idx_orders_vendor_type', 'idx_orders_vendor_deleted'],
            'products' => ['idx_prod_vendor_cat_avail_sort', 'idx_prod_vendor_available', 'idx_prod_vendor_featured', 'idx_prod_vendor_deleted'],
            'categories' => ['idx_cat_vendor_active_sort', 'idx_cat_vendor_deleted'],
        ];

        foreach ($indexes as $table => $indexNames) {
            if (Schema::hasTable($table)) {
                foreach ($indexNames as $indexName) {
                    try {
                        Schema::table($table, function (Blueprint $t) use ($indexName) {
                            $t->dropIndex($indexName);
                        });
                    } catch (Throwable $e) {
                        // Index might not exist
                    }
                }
            }
        }

        // 3. Drop Composite Foreign Keys (by column array for SQLite safety)
        $foreignKeys = [
            'users' => [['location_id', 'vendor_id']],
            'push_subscriptions' => [['location_id', 'vendor_id']],
            'analytics_logs' => [['location_id', 'vendor_id']],
            'ai_usage_logs' => [['session_id', 'vendor_id']],
            'customers' => [['location_id', 'vendor_id']],
            'payment_attempts' => [['order_id', 'vendor_id'], ['subscription_id', 'vendor_id']],
            'ai_waiter_sessions' => [['location_id', 'vendor_id'], ['order_id', 'vendor_id']],
            'waiter_calls' => [['location_id', 'vendor_id']],
            'location_product_overrides' => [['location_id', 'vendor_id'], ['product_id', 'vendor_id']],
            'categories' => [['location_id', 'vendor_id']],
            'orders' => [['location_id', 'vendor_id'], ['customer_id', 'vendor_id']],
            'products' => [['category_id', 'vendor_id']],
        ];

        foreach ($foreignKeys as $table => $fkColumnsList) {
            if (Schema::hasTable($table)) {
                foreach ($fkColumnsList as $fkColumns) {
                    try {
                        Schema::table($table, function (Blueprint $t) use ($fkColumns) {
                            $t->dropForeign($fkColumns);
                        });
                    } catch (Throwable $e) {
                        // FK might not exist
                    }
                }
            }
        }

        // 4. Drop Composite Unique Constraints
        $uniques = [
            'ai_waiter_sessions' => 'ai_sessions_id_vendor_id_unique',
            'subscription_payments' => 'sub_payments_id_vendor_id_unique',
            'orders' => 'orders_id_vendor_id_unique',
            'customers' => 'customers_id_vendor_id_unique',
            'products' => 'products_id_vendor_id_unique',
            'categories' => 'categories_id_vendor_id_unique',
            'locations' => 'locations_id_vendor_id_unique',
        ];

        foreach ($uniques as $table => $uniqueName) {
            if (Schema::hasTable($table)) {
                try {
                    Schema::table($table, function (Blueprint $t) use ($uniqueName) {
                        $t->dropUnique($uniqueName);
                    });
                } catch (Throwable $e) {
                    // Unique constraint might not exist
                }
            }
        }
    }
};
