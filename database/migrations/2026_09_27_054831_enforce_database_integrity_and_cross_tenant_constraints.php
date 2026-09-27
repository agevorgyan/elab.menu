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
        if (Schema::hasTable('locations') && ! Schema::hasIndex('locations', 'locations_id_vendor_id_unique')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->unique(['id', 'vendor_id'], 'locations_id_vendor_id_unique');
            });
        }

        if (Schema::hasTable('categories') && ! Schema::hasIndex('categories', 'categories_id_vendor_id_unique')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->unique(['id', 'vendor_id'], 'categories_id_vendor_id_unique');
            });
        }

        if (Schema::hasTable('products') && ! Schema::hasIndex('products', 'products_id_vendor_id_unique')) {
            Schema::table('products', function (Blueprint $table) {
                $table->unique(['id', 'vendor_id'], 'products_id_vendor_id_unique');
            });
        }

        if (Schema::hasTable('customers') && ! Schema::hasIndex('customers', 'customers_id_vendor_id_unique')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->unique(['id', 'vendor_id'], 'customers_id_vendor_id_unique');
            });
        }

        if (Schema::hasTable('orders') && ! Schema::hasIndex('orders', 'orders_id_vendor_id_unique')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->unique(['id', 'vendor_id'], 'orders_id_vendor_id_unique');
            });
        }

        if (Schema::hasTable('subscription_payments') && ! Schema::hasIndex('subscription_payments', 'sub_payments_id_vendor_id_unique')) {
            Schema::table('subscription_payments', function (Blueprint $table) {
                $table->unique(['id', 'vendor_id'], 'sub_payments_id_vendor_id_unique');
            });
        }

        if (Schema::hasTable('ai_waiter_sessions') && ! Schema::hasIndex('ai_waiter_sessions', 'ai_sessions_id_vendor_id_unique')) {
            Schema::table('ai_waiter_sessions', function (Blueprint $table) {
                $table->unique(['id', 'vendor_id'], 'ai_sessions_id_vendor_id_unique');
            });
        }

        // 2. Composite Foreign Keys on Child Tables (Cross-Tenant Relationship Enforcement)
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                $table->foreign(['category_id', 'vendor_id'], 'products_cat_vendor_fk')
                    ->references(['id', 'vendor_id'])
                    ->on('categories')
                    ->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreign(['location_id', 'vendor_id'], 'orders_loc_vendor_fk')
                    ->references(['id', 'vendor_id'])
                    ->on('locations')
                    ->cascadeOnDelete();

                $table->foreign(['customer_id', 'vendor_id'], 'orders_cust_vendor_fk')
                    ->references(['id', 'vendor_id'])
                    ->on('customers')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('categories')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->foreign(['location_id', 'vendor_id'], 'categories_loc_vendor_fk')
                    ->references(['id', 'vendor_id'])
                    ->on('locations')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('location_product_overrides')) {
            Schema::table('location_product_overrides', function (Blueprint $table) {
                $table->foreign(['location_id', 'vendor_id'], 'lpo_loc_vendor_fk')
                    ->references(['id', 'vendor_id'])
                    ->on('locations')
                    ->cascadeOnDelete();

                $table->foreign(['product_id', 'vendor_id'], 'lpo_prod_vendor_fk')
                    ->references(['id', 'vendor_id'])
                    ->on('products')
                    ->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('waiter_calls')) {
            Schema::table('waiter_calls', function (Blueprint $table) {
                $table->foreign(['location_id', 'vendor_id'], 'waiter_calls_loc_vendor_fk')
                    ->references(['id', 'vendor_id'])
                    ->on('locations')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('ai_waiter_sessions')) {
            Schema::table('ai_waiter_sessions', function (Blueprint $table) {
                $table->foreign(['location_id', 'vendor_id'], 'ai_sessions_loc_vendor_fk')
                    ->references(['id', 'vendor_id'])
                    ->on('locations')
                    ->nullOnDelete();

                $table->foreign(['order_id', 'vendor_id'], 'ai_sessions_order_vendor_fk')
                    ->references(['id', 'vendor_id'])
                    ->on('orders')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('payment_attempts')) {
            Schema::table('payment_attempts', function (Blueprint $table) {
                $table->foreign(['order_id', 'vendor_id'], 'payment_attempts_order_vendor_fk')
                    ->references(['id', 'vendor_id'])
                    ->on('orders')
                    ->cascadeOnDelete();

                if (Schema::hasTable('subscription_payments')) {
                    $table->foreign(['subscription_id', 'vendor_id'], 'payment_attempts_sub_vendor_fk')
                        ->references(['id', 'vendor_id'])
                        ->on('subscription_payments')
                        ->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->foreign(['location_id', 'vendor_id'], 'customers_loc_vendor_fk')
                    ->references(['id', 'vendor_id'])
                    ->on('locations')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('ai_usage_logs')) {
            Schema::table('ai_usage_logs', function (Blueprint $table) {
                $table->foreign(['session_id', 'vendor_id'], 'ai_logs_session_vendor_fk')
                    ->references(['id', 'vendor_id'])
                    ->on('ai_waiter_sessions')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('analytics_logs')) {
            Schema::table('analytics_logs', function (Blueprint $table) {
                $table->foreign(['location_id', 'vendor_id'], 'analytics_logs_loc_vendor_fk')
                    ->references(['id', 'vendor_id'])
                    ->on('locations')
                    ->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('push_subscriptions')) {
            Schema::table('push_subscriptions', function (Blueprint $table) {
                $table->foreign(['location_id', 'vendor_id'], 'push_subs_loc_vendor_fk')
                    ->references(['id', 'vendor_id'])
                    ->on('locations')
                    ->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreign(['location_id', 'vendor_id'], 'users_loc_vendor_fk')
                    ->references(['id', 'vendor_id'])
                    ->on('locations')
                    ->nullOnDelete();
            });
        }

        // 3. Composite Indexes for Tenant-First & Soft-Delete Queries
        if (Schema::hasTable('categories')) {
            Schema::table('categories', function (Blueprint $table) {
                if (! Schema::hasIndex('categories', 'idx_cat_vendor_active_sort')) {
                    $table->index(['vendor_id', 'is_active', 'sort_order'], 'idx_cat_vendor_active_sort');
                }
                if (! Schema::hasIndex('categories', 'idx_cat_vendor_deleted')) {
                    $table->index(['vendor_id', 'deleted_at'], 'idx_cat_vendor_deleted');
                }
            });
        }

        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (! Schema::hasIndex('products', 'idx_prod_vendor_cat_avail_sort')) {
                    $table->index(['vendor_id', 'category_id', 'is_available', 'sort_order'], 'idx_prod_vendor_cat_avail_sort');
                }
                if (! Schema::hasIndex('products', 'idx_prod_vendor_available')) {
                    $table->index(['vendor_id', 'is_available'], 'idx_prod_vendor_available');
                }
                if (! Schema::hasIndex('products', 'idx_prod_vendor_featured')) {
                    $table->index(['vendor_id', 'is_featured'], 'idx_prod_vendor_featured');
                }
                if (! Schema::hasIndex('products', 'idx_prod_vendor_deleted')) {
                    $table->index(['vendor_id', 'deleted_at'], 'idx_prod_vendor_deleted');
                }
            });
        }

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (! Schema::hasIndex('orders', 'idx_orders_vendor_status_created')) {
                    $table->index(['vendor_id', 'status', 'created_at'], 'idx_orders_vendor_status_created');
                }
                if (! Schema::hasIndex('orders', 'idx_orders_vendor_loc_status')) {
                    $table->index(['vendor_id', 'location_id', 'status'], 'idx_orders_vendor_loc_status');
                }
                if (! Schema::hasIndex('orders', 'idx_orders_vendor_type')) {
                    $table->index(['vendor_id', 'type'], 'idx_orders_vendor_type');
                }
                if (! Schema::hasIndex('orders', 'idx_orders_vendor_deleted')) {
                    $table->index(['vendor_id', 'deleted_at'], 'idx_orders_vendor_deleted');
                }
            });
        }

        if (Schema::hasTable('waiter_calls')) {
            Schema::table('waiter_calls', function (Blueprint $table) {
                if (! Schema::hasIndex('waiter_calls', 'idx_waiter_calls_vendor_status_created')) {
                    $table->index(['vendor_id', 'status', 'created_at'], 'idx_waiter_calls_vendor_status_created');
                }
                if (! Schema::hasIndex('waiter_calls', 'idx_waiter_calls_vendor_loc_status')) {
                    $table->index(['vendor_id', 'location_id', 'status'], 'idx_waiter_calls_vendor_loc_status');
                }
                if (! Schema::hasIndex('waiter_calls', 'idx_waiter_calls_vendor_table_status')) {
                    $table->index(['vendor_id', 'table_number', 'status'], 'idx_waiter_calls_vendor_table_status');
                }
            });
        }

        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                if (! Schema::hasIndex('customers', 'idx_customers_vendor_phone')) {
                    $table->index(['vendor_id', 'phone'], 'idx_customers_vendor_phone');
                }
                if (! Schema::hasIndex('customers', 'idx_customers_vendor_email')) {
                    $table->index(['vendor_id', 'email'], 'idx_customers_vendor_email');
                }
            });
        }

        if (Schema::hasTable('ai_waiter_sessions')) {
            Schema::table('ai_waiter_sessions', function (Blueprint $table) {
                if (! Schema::hasIndex('ai_waiter_sessions', 'idx_ai_sessions_vendor_status')) {
                    $table->index(['vendor_id', 'status'], 'idx_ai_sessions_vendor_status');
                }
                if (! Schema::hasIndex('ai_waiter_sessions', 'idx_ai_sessions_vendor_loc_status')) {
                    $table->index(['vendor_id', 'location_id', 'status'], 'idx_ai_sessions_vendor_loc_status');
                }
            });
        }

        if (Schema::hasTable('payment_attempts')) {
            Schema::table('payment_attempts', function (Blueprint $table) {
                if (! Schema::hasIndex('payment_attempts', 'idx_payment_attempts_vendor_status')) {
                    $table->index(['vendor_id', 'status'], 'idx_payment_attempts_vendor_status');
                }
                if (! Schema::hasIndex('payment_attempts', 'idx_payment_attempts_vendor_order')) {
                    $table->index(['vendor_id', 'order_id'], 'idx_payment_attempts_vendor_order');
                }
                if (! Schema::hasIndex('payment_attempts', 'idx_payment_attempts_vendor_ref')) {
                    $table->index(['vendor_id', 'merchant_reference'], 'idx_payment_attempts_vendor_ref');
                }
            });
        }

        if (Schema::hasTable('vendor_storage_files')) {
            Schema::table('vendor_storage_files', function (Blueprint $table) {
                if (! Schema::hasIndex('vendor_storage_files', 'idx_storage_files_vendor_status')) {
                    $table->index(['vendor_id', 'status'], 'idx_storage_files_vendor_status');
                }
                if (! Schema::hasIndex('vendor_storage_files', 'idx_storage_files_vendor_deleted')) {
                    $table->index(['vendor_id', 'deleted_at'], 'idx_storage_files_vendor_deleted');
                }
            });
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
