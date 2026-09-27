<?php

namespace App\Models;

use App\Models\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorDeletionJob extends Model
{
    use BelongsToVendor, HasFactory;

    protected $fillable = [
        'vendor_id',
        'uuid',
        'status',
        'current_step',
        'steps_completed',
        'attempt_count',
        'error_message',
        'error_trace',
        'requested_by',
        'reason',
        'retention_ends_at',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'steps_completed' => 'array',
        'attempt_count' => 'integer',
        'retention_ends_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class)->withTrashed();
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function isStepCompleted(string $step): bool
    {
        $completed = $this->steps_completed ?? [];

        return isset($completed[$step]);
    }

    public function markStepCompleted(string $step, array $details = []): void
    {
        $completed = $this->steps_completed ?? [];
        $completed[$step] = array_merge([
            'completed_at' => now()->toIso8601String(),
        ], $details);

        $this->update([
            'current_step' => $step,
            'steps_completed' => $completed,
        ]);
    }

    public function markFailed(string $step, \Throwable $e): void
    {
        $this->update([
            'status' => 'failed',
            'current_step' => $step,
            'error_message' => $e->getMessage(),
            'error_trace' => substr($e->getTraceAsString(), 0, 5000),
        ]);
    }
}
