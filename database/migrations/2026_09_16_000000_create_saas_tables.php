<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Menu Templates
        Schema::create('menu_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('preview_image')->nullable();
            $table->text('description')->nullable();
            $table->json('default_config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Vendors
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type')->default('restaurant'); // restaurant, cafe, hotel
            $table->string('logo')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('currency', 10)->default('AMD');
            $table->string('custom_domain')->nullable();

            // Branding & Design
            $table->foreignId('menu_template_id')->nullable()->constrained('menu_templates')->nullOnDelete();
            $table->string('primary_color', 20)->default('#e11d48');
            $table->string('secondary_color', 20)->default('#4f46e5');
            $table->string('theme_mode', 10)->default('dark'); // dark, light, system
            $table->text('custom_css')->nullable();

            // Features & Limits
            $table->string('subscription_plan')->default('pro'); // basic, pro, enterprise
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Update Users Table for multi-tenancy & roles
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('vendor_id')->nullable()->after('id')->constrained('vendors')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->after('vendor_id');
            $table->string('role')->default('staff')->after('email'); // superadmin, vendor_owner, manager, staff
            $table->string('phone')->nullable()->after('email');
        });

        // 3. Locations
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('whatsapp_number')->nullable();
            $table->json('opening_hours')->nullable();
            $table->integer('table_count')->default(20);
            $table->boolean('allow_dine_in_orders')->default(true);
            $table->boolean('allow_whatsapp_orders')->default(true);
            $table->decimal('minimum_order_amount', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['vendor_id', 'slug']);
        });

        // 4. Allergens (EU regulated + custom)
        Schema::create('allergens', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->json('name_translations')->nullable();
            $table->string('icon')->nullable();
            $table->timestamps();
        });

        // 5. Categories
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete(); // Null = all locations
            $table->string('name');
            $table->json('name_translations')->nullable();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 6. Products / Dishes
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->string('name');
            $table->json('name_translations')->nullable();
            $table->text('description')->nullable();
            $table->json('description_translations')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('image')->nullable();
            $table->json('gallery')->nullable();

            // Dietary & Nutrition
            $table->json('dietary_tags')->nullable(); // vegan, gluten-free, halal, etc.
            $table->integer('calories')->nullable();
            $table->decimal('protein_g', 6, 1)->nullable();
            $table->decimal('carbs_g', 6, 1)->nullable();
            $table->decimal('fat_g', 6, 1)->nullable();
            $table->integer('preparation_time_min')->nullable();

            // Flags
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_available')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 7. Product Variations (Sizes, Portion types)
        Schema::create('product_variations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('name');
            $table->json('name_translations')->nullable();
            $table->decimal('price', 10, 2);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // 8. Product Allergen Pivot
        Schema::create('product_allergens', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('allergen_id')->constrained('allergens')->cascadeOnDelete();
            $table->primary(['product_id', 'allergen_id']);
        });

        // 9. Location Product Overrides (Per-location pricing or stock toggle)
        Schema::create('location_product_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('override_price', 10, 2)->nullable();
            $table->boolean('is_available')->default(true);
            $table->timestamps();

            $table->unique(['location_id', 'product_id']);
        });

        // 10. Orders
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->string('order_number')->unique();
            $table->string('table_number')->nullable();
            $table->enum('type', ['dine_in', 'takeaway', 'delivery', 'whatsapp'])->default('dine_in');
            $table->decimal('total_amount', 10, 2);
            $table->enum('status', ['pending', 'accepted', 'preparing', 'ready', 'completed', 'cancelled'])->default('pending');
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 11. Order Items
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('product_name');
            $table->string('variation_name')->nullable();
            $table->decimal('unit_price', 10, 2);
            $table->integer('quantity');
            $table->decimal('subtotal', 10, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 12. Analytics Logs
        Schema::create('analytics_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->cascadeOnDelete();
            $table->string('channel')->default('dine_in'); // dine_in, online, qr_scan
            $table->string('user_agent')->nullable();
            $table->string('ip_address')->nullable();
            $table->date('visit_date');
            $table->timestamps();
        });

        // 13. Push Subscriptions
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->cascadeOnDelete();
            $table->text('endpoint');
            $table->string('public_key')->nullable();
            $table->string('auth_token')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
        Schema::dropIfExists('analytics_logs');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('location_product_overrides');
        Schema::dropIfExists('product_allergens');
        Schema::dropIfExists('product_variations');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('allergens');
        Schema::dropIfExists('locations');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['vendor_id']);
            $table->dropColumn(['vendor_id', 'location_id', 'role', 'phone']);
        });

        Schema::dropIfExists('vendors');
        Schema::dropIfExists('menu_templates');
    }
};
