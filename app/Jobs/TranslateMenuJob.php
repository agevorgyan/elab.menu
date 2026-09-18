<?php

namespace App\Jobs;

use App\Models\Category;
use App\Services\AiMenuService;
use App\Services\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TranslateMenuJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $vendorId,
        public string $targetLang
    ) {}

    /**
     * Execute the menu translation job asynchronously.
     */
    public function handle(AiMenuService $aiService, TenantContext $tenantContext): void
    {
        $tenantContext->runInTenantContext($this->vendorId, function () use ($aiService) {
            $categories = Category::where('vendor_id', $this->vendorId)->with('products')->get();

            $itemsToTranslate = [];
            foreach ($categories as $cat) {
                $itemsToTranslate["cat_{$cat->id}"] = $cat->name;
                foreach ($cat->products as $prod) {
                    $itemsToTranslate["prod_name_{$prod->id}"] = $prod->name;
                    if (!empty($prod->description)) {
                        $itemsToTranslate["prod_desc_{$prod->id}"] = $prod->description;
                    }
                }
            }

            if (empty($itemsToTranslate)) {
                return;
            }

            $translated = $aiService->translateMenuBatch($itemsToTranslate, $this->targetLang);

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
                }
            }
        });
    }
}
