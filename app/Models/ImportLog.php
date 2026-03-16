<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportLog extends Model
{
    protected $fillable = [
        'user_id',
        'excel_file_name',
        'zip_file_name',
        'total_rows',
        'successful_imports',
        'failed_imports',
        'skipped_imports',
        'status',
        'error_message',
        'started_at',
        'completed_at',
        'processing_time_seconds',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'total_rows' => 'integer',
        'successful_imports' => 'integer',
        'failed_imports' => 'integer',
        'skipped_imports' => 'integer',
        'processing_time_seconds' => 'integer',
    ];

    /**
     * Get the user that owns the import log
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all import details for this log
     */
    public function details(): HasMany
    {
        return $this->hasMany(ImportDetail::class);
    }

    /**
     * Get successful import details
     */
    public function successfulDetails(): HasMany
    {
        return $this->hasMany(ImportDetail::class)->where('status', 'success');
    }

    /**
     * Get failed import details
     */
    public function failedDetails(): HasMany
    {
        return $this->hasMany(ImportDetail::class)->where('status', 'failed');
    }

    /**
     * Get skipped import details
     */
    public function skippedDetails(): HasMany
    {
        return $this->hasMany(ImportDetail::class)->where('status', 'skipped');
    }

    /**
     * Calculate success rate percentage
     */
    public function calculateSuccessRate(): float
    {
        if ($this->total_rows == 0) return 0;
        return round(($this->successful_imports / $this->total_rows) * 100, 2);
    }

    /**
     * Check if import is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if import is processing
     */
    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Check if import has failed
     */
    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Get formatted processing time
     */
    public function getFormattedProcessingTime(): string
    {
        if (!$this->processing_time_seconds) {
            return 'N/A';
        }

        $seconds = $this->processing_time_seconds;
        
        if ($seconds < 60) {
            return "{$seconds} ثانية";
        }
        
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        
        if ($minutes < 60) {
            return "{$minutes} دقيقة و {$remainingSeconds} ثانية";
        }
        
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        
        return "{$hours} ساعة و {$remainingMinutes} دقيقة";
    }
}

