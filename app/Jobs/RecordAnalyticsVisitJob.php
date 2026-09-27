<?php

namespace App\Jobs;

use App\Jobs\Concerns\TenantAwareJob;
use App\Jobs\Contracts\TenantJobInterface;
use App\Models\AnalyticsLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecordAnalyticsVisitJob implements ShouldQueue, TenantJobInterface
{
    use InteractsWithQueue, Queueable, SerializesModels, TenantAwareJob;

    /**
     * Create a new job instance.
     *
     * @param  array  $data  Analytics visit payload
     */
    public function __construct(
        public array $data,
        ?int $vendorId = null,
        ?string $idempotencyKey = null
    ) {
        $this->vendorId = $vendorId ?? (int) ($data['vendor_id'] ?? 0);
        $this->idempotencyKey = $idempotencyKey ?? (
            isset($data['visit_hash']) ? 'analytics_'.$this->vendorId.'_'.$data['visit_hash'] : null
        );
    }

    /**
     * Execute the job in the queue worker.
     */
    public function handle(): void
    {
        try {
            AnalyticsLog::create($this->data);
        } catch (\Throwable $e) {
            // Silently ignore analytics logging errors in worker
        }
    }
}
