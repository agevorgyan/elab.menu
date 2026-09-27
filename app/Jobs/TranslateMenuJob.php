<?php

namespace App\Jobs;

use App\Jobs\Concerns\TenantAwareJob;
use App\Jobs\Contracts\TenantJobInterface;
use App\Models\Category;
use App\Models\Vendor;
use App\Services\AiMenuService;
use App\Services\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TranslateMenuJob implements ShouldQueue, TenantJobInterface
{
    use InteractsWithQueue, Queueable, SerializesModels, TenantAwareJob;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [15, 60, 180];
    }

    /**
     * Create a new job instance.
     */
    public function __construct(
        int $vendorId,
        public string $targetLang,
        public bool $overwriteExisting = true,
        ?string $idempotencyKey = null
    ) {
        $this->vendorId = $vendorId;
        $this->idempotencyKey = $idempotencyKey ?? "menu_translate_{$vendorId}_{$targetLang}_".($overwriteExisting ? '1' : '0');
    }

    /**
     * Execute the menu translation job asynchronously.
     */
    public function handle(AiMenuService $aiService, ?TenantContext $tenantContext = null): void
    {
        $context = $tenantContext ?? app(TenantContext::class);
        $context->runInTenantContext($this->vendorId, function () use ($aiService) {
            $categories = Category::where('vendor_id', $this->vendorId)->with('products.variations')->get();

            $itemsToTranslate = [];
            foreach ($categories as $cat) {
                $cTrans = $cat->name_translations ?? [];
                if ($this->overwriteExisting || empty($cTrans[$this->targetLang])) {
                    $itemsToTranslate["cat_{$cat->id}"] = $cat->name;
                }
                foreach ($cat->products as $prod) {
                    $pNameTrans = $prod->name_translations ?? [];
                    if ($this->overwriteExisting || empty($pNameTrans[$this->targetLang])) {
                        $itemsToTranslate["prod_name_{$prod->id}"] = $prod->name;
                    }
                    if (! empty($prod->description)) {
                        $pDescTrans = $prod->description_translations ?? [];
                        if ($this->overwriteExisting || empty($pDescTrans[$this->targetLang])) {
                            $itemsToTranslate["prod_desc_{$prod->id}"] = $prod->description;
                        }
                    }
                    foreach ($prod->variations as $var) {
                        if (! empty($var->name) && ! in_array($var->name, ['Standard', 'Standard Portion'])) {
                            $vTrans = $var->name_translations ?? [];
                            if ($this->overwriteExisting || empty($vTrans[$this->targetLang])) {
                                $itemsToTranslate["var_name_{$var->id}"] = $var->name;
                            }
                        }
                    }
                }
            }

            if (empty($itemsToTranslate)) {
                return;
            }

            $vendor = Vendor::find($this->vendorId);
            $translated = $aiService->translateMenuBatch($itemsToTranslate, $this->targetLang, $vendor);

            foreach ($categories as $cat) {
                $cTrans = $cat->name_translations ?? [];
                if (isset($translated["cat_{$cat->id}"])) {
                    $cTrans[$this->targetLang] = $translated["cat_{$cat->id}"];
                    $cat->update(['name_translations' => $cTrans]);
                }

                foreach ($cat->products as $prod) {
                    $pNameTrans = $prod->name_translations ?? [];
                    if (isset($translated["prod_name_{$prod->id}"])) {
                        $pNameTrans[$this->targetLang] = $translated["prod_name_{$prod->id}"];
                    }

                    $pDescTrans = $prod->description_translations ?? [];
                    if (isset($translated["prod_desc_{$prod->id}"])) {
                        $pDescTrans[$this->targetLang] = $translated["prod_desc_{$prod->id}"];
                    }

                    $prod->update([
                        'name_translations' => $pNameTrans,
                        'description_translations' => $pDescTrans,
                    ]);

                    foreach ($prod->variations as $var) {
                        if (isset($translated["var_name_{$var->id}"])) {
                            $vTrans = $var->name_translations ?? [];
                            $vTrans[$this->targetLang] = $translated["var_name_{$var->id}"];
                            $var->update(['name_translations' => $vTrans]);
                        }
                    }
                }
            }
        });
    }
}
