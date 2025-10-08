<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProductFile extends Model
{
    protected $fillable = [
        'product_id',
        'file_name',
        'file_path',
        'file_size',
        'file_type',
        'mime_type',
        'display_name',
        'description',
        'sort_order',
        'is_active',
        'is_featured',
        'download_count',
        'last_downloaded_at',
        'file_category',
        'metadata',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'file_size' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'download_count' => 'integer',
        'last_downloaded_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the product that owns this file
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope for active files
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for featured files
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope for ordered files
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Scope for specific product
     */
    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope for specific file category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('file_category', $category);
    }

    /**
     * Get file size in human readable format
     */
    public function getFileSizeHumanAttribute()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get file URL for download
     */
    public function getDownloadUrlAttribute()
    {
        return route('products.files.download', ['product' => $this->product_id, 'file' => $this->id]);
    }

    /**
     * Get file icon based on type/category
     */
    public function getFileIconAttribute()
    {
        $icons = [
            'catalog' => 'fas fa-book',
            'manual' => 'fas fa-tools',
            'specs' => 'fas fa-list-alt',
            'warranty' => 'fas fa-shield-alt',
            'certificate' => 'fas fa-certificate',
            'drawing' => 'fas fa-drafting-compass',
        ];

        return $icons[$this->file_category] ?? 'fas fa-file-pdf';
    }

    /**
     * Get file category display name
     */
    public function getFileCategoryDisplayAttribute()
    {
        $categories = [
            'catalog' => 'كتالوج المنتج',
            'manual' => 'دليل المستخدم',
            'specs' => 'المواصفات التقنية',
            'warranty' => 'ضمان المنتج',
            'certificate' => 'شهادات الجودة',
            'drawing' => 'رسوم تقنية',
            'other' => 'ملفات أخرى',
        ];

        return $categories[$this->file_category] ?? 'ملف PDF';
    }

    /**
     * Check if file exists in storage
     */
    public function fileExists()
    {
        return Storage::disk('public')->exists($this->file_path);
    }

    /**
     * Get full file path
     */
    public function getFullFilePathAttribute()
    {
        return Storage::disk('public')->path($this->file_path);
    }

    /**
     * Get file URL (for preview if needed)
     */
    public function getFileUrlAttribute()
    {
        return Storage::disk('public')->url($this->file_path);
    }

    /**
     * Increment download count
     */
    public function incrementDownload()
    {
        $this->increment('download_count');
        $this->update(['last_downloaded_at' => now()]);
    }

    /**
     * Delete file from storage when model is deleted
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($file) {
            if ($file->fileExists()) {
                Storage::disk('public')->delete($file->file_path);
            }
        });
    }

    /**
     * Get metadata value by key
     */
    public function getMetadata($key, $default = null)
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Set metadata value
     */
    public function setMetadata($key, $value)
    {
        $metadata = $this->metadata ?? [];
        $metadata[$key] = $value;
        $this->metadata = $metadata;
        $this->save();
    }

    /**
     * Scope to search files
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('display_name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('file_name', 'like', "%{$search}%")
              ->orWhere('file_category', 'like', "%{$search}%");
        });
    }

    /**
     * Get popular files (most downloaded)
     */
    public static function popular($limit = 10)
    {
        return static::orderBy('download_count', 'desc')->limit($limit);
    }

    /**
     * Get recent files
     */
    public static function recent($limit = 10)
    {
        return static::orderBy('created_at', 'desc')->limit($limit);
    }
}