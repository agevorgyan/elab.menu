<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\ContentTranslation;
use App\Models\Product;
use App\Models\TranslationHistory;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Localization\Providers\MockTranslationProvider;
use App\Services\Localization\TranslationService;
use App\Services\Localization\TranslationValidator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TranslationServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected TranslationService $service;

    protected MockTranslationProvider $mockProvider;

    protected Vendor $vendor;

    protected Category $category;

    protected Product $product;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockProvider = new MockTranslationProvider;
        $validator = new TranslationValidator;
        $this->service = new TranslationService($this->mockProvider, $validator);

        $this->vendor = Vendor::create([
            'name' => 'Test Restaurant',
            'slug' => 'test-restaurant-'.uniqid(),
        ]);

        $this->category = Category::create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Desserts',
        ]);

        $this->product = Product::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $this->category->id,
            'name' => 'Chocolate Cake',
            'description' => 'Rich dark chocolate cake',
            'price' => 2500,
        ]);

        $this->user = User::factory()->create([
            'vendor_id' => $this->vendor->id,
        ]);
    }

    public function test_translate_model_field_creates_draft_and_history(): void
    {
        $this->mockProvider->setTranslation('Chocolate Cake', 'hy', 'Շոկոլադե տորթ');

        $translation = $this->service->translateModelField(
            $this->product,
            'name',
            'hy',
            'en',
            $this->user
        );

        $this->assertInstanceOf(ContentTranslation::class, $translation);
        $this->assertEquals('hy', $translation->locale);
        $this->assertEquals('name', $translation->field);
        $this->assertEquals('Շոկոլադե տորթ', $translation->translated_text);
        $this->assertEquals(ContentTranslation::STATUS_DRAFT, $translation->status);

        // Verify history was recorded
        $this->assertDatabaseHas('translation_histories', [
            'content_translation_id' => $translation->id,
            'translation' => 'Շոկոլադե տորթ',
        ]);
    }

    public function test_mandatory_rule_ai_retranslation_does_not_silently_overwrite_approved_or_published(): void
    {
        // 1. Create an approved translation
        $translation = ContentTranslation::create([
            'translatable_type' => $this->product->getMorphClass(),
            'translatable_id' => $this->product->id,
            'vendor_id' => $this->vendor->id,
            'field' => 'name',
            'locale' => 'hy',
            'source_locale' => 'en',
            'source_text' => 'Chocolate Cake',
            'translated_text' => 'Հաստատված Շոկոլադե տորթ',
            'status' => ContentTranslation::STATUS_APPROVED,
        ]);

        $this->assertTrue($translation->isApproved());

        // 2. Mock a different AI translation
        $this->mockProvider->setTranslation('Chocolate Cake', 'hy', 'Նոր AI առաջարկ');

        // 3. Request re-translation
        $updated = $this->service->translateModelField(
            $this->product,
            'name',
            'hy',
            'en',
            $this->user
        );

        // 4. VERIFY: The translated_text MUST NOT be overwritten!
        $this->assertEquals('Հաստատված Շոկոլադե տորթ', $updated->fresh()->translated_text);
        $this->assertTrue($updated->fresh()->isApproved());
        $this->assertTrue($updated->fresh()->needs_review);

        // Verify AI proposal was saved in metadata
        $meta = $updated->fresh()->metadata;
        $this->assertNotNull($meta['ai_proposal'] ?? null);
        $this->assertEquals('Նոր AI առաջարկ', $meta['ai_proposal']['proposed_text']);
    }

    public function test_manual_editing_save_draft_and_approval_workflow(): void
    {
        $this->mockProvider->setTranslation('Chocolate Cake', 'ru', 'Шоколадный торт');

        $translation = $this->service->translateModelField(
            $this->product,
            'name',
            'ru',
            'en',
            $this->user
        );

        // Edit manually
        $this->service->saveDraft($translation, 'Фирменный шоколадный торт', $this->user, 'Corrected to brand voice');
        $this->assertEquals('Фирменный шоколадный торт', $translation->fresh()->translated_text);
        $this->assertEquals(ContentTranslation::SOURCE_HUMAN, $translation->fresh()->source_type);

        // Approve
        $this->service->approve($translation, $this->user);
        $this->assertEquals(ContentTranslation::STATUS_APPROVED, $translation->fresh()->status);
        $this->assertTrue($translation->fresh()->isApproved());

        // Publish and verify sync to Product name_translations JSON column
        $this->service->publish($translation, $this->user);
        $this->assertEquals(ContentTranslation::STATUS_PUBLISHED, $translation->fresh()->status);

        $productTranslations = $this->product->fresh()->name_translations;
        $this->assertEquals('Фирменный шоколадный торт', $productTranslations['ru']);
    }

    public function test_restoring_version_from_history(): void
    {
        $this->mockProvider->setTranslation('Chocolate Cake', 'hy', 'Տարբերակ 1');

        $translation = $this->service->translateModelField(
            $this->product,
            'name',
            'hy',
            'en',
            $this->user
        );

        $history1 = TranslationHistory::where('content_translation_id', $translation->id)->first();

        // Update to version 2
        $this->service->saveDraft($translation, 'Տարբերակ 2 (սխալ)', $this->user);
        $this->assertEquals('Տարբերակ 2 (սխալ)', $translation->fresh()->translated_text);

        // Restore version 1
        $this->service->restoreVersion($translation, $history1->id, $this->user);
        $this->assertEquals('Տարբերակ 1', $translation->fresh()->translated_text);
    }
}
