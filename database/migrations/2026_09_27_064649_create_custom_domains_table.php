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
        Schema::create('custom_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->string('domain', 255);
            $table->string('normalized_domain', 255)->unique();
            $table->boolean('is_primary')->default(false);
            $table->string('verification_token', 64);
            $table->string('verification_method', 32)->default('dns_txt');
            $table->timestamp('verified_at')->nullable();
            $table->string('dns_status', 32)->default('pending');
            $table->timestamp('dns_detected_at')->nullable();
            $table->string('ssl_status', 32)->default('pending');
            $table->timestamp('ssl_expires_at')->nullable();
            $table->string('status', 32)->default('pending'); // pending, verifying, active, suspended, failed
            $table->text('failure_reason')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();

            $table->index(['vendor_id', 'is_primary']);
            $table->index(['status', 'verified_at']);
        });

        // Migrate any existing vendor custom_domain values into custom_domains entity
        if (Schema::hasTable('vendors') && Schema::hasColumn('vendors', 'custom_domain')) {
            $existingVendors = DB::table('vendors')
                ->whereNotNull('custom_domain')
                ->where('custom_domain', '!=', '')
                ->get();

            foreach ($existingVendors as $v) {
                $clean = strtolower(trim(preg_replace('#^https?://#i', '', $v->custom_domain)));
                $clean = explode('/', $clean)[0];
                $clean = explode(':', $clean)[0];
                $clean = rtrim($clean, '.');

                if (! empty($clean)) {
                    DB::table('custom_domains')->insertOrIgnore([
                        'vendor_id' => $v->id,
                        'domain' => $v->custom_domain,
                        'normalized_domain' => $clean,
                        'is_primary' => true,
                        'verification_token' => 'elab-verify-legacy-'.bin2hex(random_bytes(16)),
                        'verification_method' => 'dns_txt',
                        'verified_at' => now(),
                        'dns_status' => 'detected',
                        'dns_detected_at' => now(),
                        'ssl_status' => 'active',
                        'status' => 'active',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_domains');
    }
};
