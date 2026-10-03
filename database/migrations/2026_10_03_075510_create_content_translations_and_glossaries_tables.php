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
        // 1. Content Translations (Normalized Translatable Entity Attributes)
        Schema::create('content_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->string('translatable_type', 120);
            $table->unsignedBigInteger('translatable_id');
            $table->string('field', 64);
            $table->string('locale', 10);
            $table->string('source_locale', 10)->default('en');
            $table->text('source_text')->nullable();
            $table->string('source_hash', 64)->nullable(); // SHA-256 hash for change detection
            $table->text('translation')->nullable();
            $table->string('status', 24)->default('draft'); // missing, queued, translating, draft, needs_review, approved, published, rejected, failed, stale
            $table->string('source_type', 24)->default('manual'); // ai, manual, import
            $table->string('ai_provider', 50)->nullable();
            $table->string('ai_model', 100)->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_outdated')->default(false);
            $table->timestamps();

            // Indexes & Unique Constraints
            $table->unique(
                ['vendor_id', 'translatable_type', 'translatable_id', 'field', 'locale'],
                'uq_content_trans_entity_field_locale'
            );
            $table->index(['vendor_id', 'status', 'locale'], 'idx_content_trans_vendor_status_locale');
            $table->index(['translatable_type', 'translatable_id'], 'idx_content_trans_polymorphic');
        });

        // 2. Translation Version History
        Schema::create('translation_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_translation_id')->constrained('content_translations')->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->integer('version_number')->default(1);
            $table->text('source_text')->nullable();
            $table->text('translation')->nullable();
            $table->string('status', 24)->default('draft');
            $table->string('source_type', 24)->default('manual');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable(); // diffs, tokens, execution time, provider details
            $table->timestamps();

            $table->index(['content_translation_id', 'version_number'], 'idx_trans_history_version');
            $table->index(['vendor_id', 'created_at'], 'idx_trans_history_vendor_date');
        });

        // 3. Translation Terminology & Glossaries
        Schema::create('translation_glossaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->cascadeOnDelete(); // null = platform-wide glossary
            $table->string('term', 255);
            $table->string('source_locale', 10)->default('en');
            $table->string('target_locale', 10)->default('*'); // '*' = applies to all target locales
            $table->string('translation', 255)->nullable(); // preferred translation
            $table->json('forbidden_terms')->nullable(); // terms AI must NEVER use
            $table->boolean('case_sensitive')->default(false);
            $table->text('description')->nullable(); // culinary context or meaning
            $table->timestamps();

            $table->index(['vendor_id', 'target_locale'], 'idx_glossary_vendor_locale');
            $table->index('term', 'idx_glossary_term');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('translation_glossaries');
        Schema::dropIfExists('translation_histories');
        Schema::dropIfExists('content_translations');
    }
};
