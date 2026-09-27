<?php

namespace App\Models;

use App\Models\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class VendorStorageFile extends Model
{
    use BelongsToVendor, HasFactory, SoftDeletes;

    protected $table = 'vendor_storage_files';

    protected $fillable = [
        'vendor_id',
        'uuid',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size_bytes',
        'checksum',
        'entity_type',
        'entity_id',
        'status',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'entity_id' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (VendorStorageFile $file) {
            if ($file->vendor_id && $file->entity_type && $file->entity_id) {
                $class = $file->entity_type;
                if (class_exists($class)) {
                    $entity = $class::withoutGlobalScopes()->find($file->entity_id);
                    if ($entity && isset($entity->vendor_id) && (int) $entity->vendor_id !== (int) $file->vendor_id) {
                        throw new \InvalidArgumentException('Cross-vendor entity attached to vendor storage file.');
                    }
                }
            }
        });
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the accessible public URL for the file.
     */
    public function getUrl(): string
    {
        if ($this->disk === 'public') {
            return '/storage/'.ltrim($this->path, '/');
        }

        return Storage::disk($this->disk)->url($this->path);
    }

    /**
     * Get absolute path on server filesystem.
     */
    public function getAbsolutePath(): string
    {
        return Storage::disk($this->disk)->path($this->path);
    }

    /**
     * Check if physical file exists on configured disk.
     */
    public function fileExistsOnDisk(): bool
    {
        return Storage::disk($this->disk)->exists($this->path);
    }
}
