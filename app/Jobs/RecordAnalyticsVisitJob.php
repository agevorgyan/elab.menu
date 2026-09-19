<?php

namespace App\Jobs;

use App\Models\AnalyticsLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecordAnalyticsVisitJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param  array  $data  Analytics visit payload
     */
    public function __construct(
        public array $data
    ) {}

    /**
     * Execute the job in the queue worker.
     */
    public function handle(): void
    {
        try {
            AnalyticsLog::create($this->data);
        } catch (\Throwable $e) {
            // Silently ignore analytics logging errors in queue
        }
    }
}
