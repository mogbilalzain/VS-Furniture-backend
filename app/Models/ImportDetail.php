<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportDetail extends Model
{
    protected $fillable = [
        'import_log_id',
        'row_number',
        'product_name',
        'sku',
        'status',
        'error_message',
        'product_id',
        'images_uploaded',
        'matched_images',
    ];

    protected $casts = [
        'row_number' => 'integer',
        'images_uploaded' => 'integer',
        'matched_images' => 'array',
    ];

    /**
     * Get the import log that owns this detail
     */
    public function importLog(): BelongsTo
    {
        return $this->belongsTo(ImportLog::class);
    }

    /**
     * Get the product that was created
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Check if import was successful
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    /**
     * Check if import failed
     */
    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if import was skipped
     */
    public function wasSkipped(): bool
    {
        return $this->status === 'skipped';
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeColor(): string
    {
        return match($this->status) {
            'success' => 'green',
            'failed' => 'red',
            'skipped' => 'yellow',
            default => 'gray'
        };
    }

    /**
     * Get status label in Arabic
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            'success' => 'نجح',
            'failed' => 'فشل',
            'skipped' => 'تم التخطي',
            default => 'غير معروف'
        };
    }
}

