<?php

namespace App\Jobs;

use App\Jobs\Concerns\TenantAwareJob;
use App\Jobs\Contracts\TenantJobInterface;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Localization\TranslationService;
use App\Services\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TranslateContentJob implements ShouldQueue, TenantJobInterface
{
    use InteractsWithQueue, Queueable, SerializesModels, TenantAwareJob;

    public int $tries = 3;

    public int $timeout = 180;

    /**
     * Exponential backoff in seconds.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [15, 60, 180];
    }

    public function __construct(
        int $vendorId,
        public string $targetLocale,
        public ?string $sourceLocale = null,
        public ?string $modelType = null,
        public ?int $modelId = null,
        public ?int $userId = null,
        ?string $idempotencyKey = null
    ) {
        $this->vendorId = $vendorId;
        $this->idempotencyKey = $idempotencyKey ?? "content_translate_{$vendorId}_{$targetLocale}_".($modelType ?? 'all').'_'.($modelId ?? '0').'_'.hrtime(true);
    }

    public function handle(TranslationService $translationService, ?TenantContext $tenantContext = null): void
    {
        $context = $tenantContext ?? app(TenantContext::class);
        $context->runInTenantContext($this->vendorId, function () use ($translationService) {
            $vendor = Vendor::find($this->vendorId);
            if (! $vendor) {
                Log::warning("TranslateContentJob aborted: Vendor #{$this->vendorId} not found.");

                return;
            }

            $user = $this->userId ? User::find($this->userId) : null;
            $sourceLocale = $this->sourceLocale ?: $vendor->getDefaultLanguageCode();

            if ($this->modelType && $this->modelId) {
                // Translate single entity
                $modelClass = match ($this->modelType) {
                    'Product', Product::class => Product::class,
                    'Category', Category::class => Category::class,
                    default => null,
                };

                if ($modelClass) {
                    $entity = $modelClass::where('vendor_id', $this->vendorId)->find($this->modelId);
                    if ($entity) {
                        $translationService->translateModel($entity, $this->targetLocale, $sourceLocale, $user);
                    }
                }

                return;
            }

            // Batch translate all vendor categories & products
            $categories = Category::where('vendor_id', $this->vendorId)->get();
            foreach ($categories as $category) {
                try {
                    $translationService->translateModel($category, $this->targetLocale, $sourceLocale, $user);
                } catch (\Throwable $e) {
                    Log::error("Failed to translate category #{$category->id}: ".$e->getMessage());
                }
            }

            $products = Product::where('vendor_id', $this->vendorId)->get();
            foreach ($products as $product) {
                try {
                    $translationService->translateModel($product, $this->targetLocale, $sourceLocale, $user);
                } catch (\Throwable $e) {
                    Log::error("Failed to translate product #{$product->id}: ".$e->getMessage());
                }
            }
        });
    }
}
