<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProductImage extends Model
{
    protected $fillable = [
        'product_id',
        'image_url',
        'alt_text',
        'title',
        'sort_order',
        'is_primary',
        'is_active',
        'is_featured',
        'image_type',
        'metadata',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'sort_order' => 'integer',
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get the product that owns this image
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope for active images
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for primary images
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope for featured images
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope for ordered images
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
     * Scope for specific image type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('image_type', $type);
    }

    /**
     * Get full image URL (handle both local and external URLs).
     *
     * كل المسارات تطبَّع إلى `{APP_URL}/uploads/images/products/...` عبر
     * ImageHelper الموحَّد، مع المرور دون تعديل لأي رابط مطلق (http/https).
     */
    public function getFullImageUrlAttribute()
    {
        return \App\Helpers\ImageHelper::buildFullUrl($this->image_url, 'products');
    }

    /**
     * Get image dimensions from metadata
     */
    public function getImageDimensionsAttribute()
    {
        return [
            'width' => $this->metadata['width'] ?? null,
            'height' => $this->metadata['height'] ?? null,
        ];
    }

    /**
     * Get image file size from metadata
     */
    public function getImageSizeAttribute()
    {
        return $this->metadata['size'] ?? null;
    }

    /**
     * Get formatted file size
     */
    public function getFormattedSizeAttribute()
    {
        $size = $this->image_size;
        if (!$size) return null;

        $units = ['B', 'KB', 'MB', 'GB'];
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }

        return round($size, 2) . ' ' . $units[$i];
    }

    /**
     * Check if image file exists.
     *
     * يفحص في كلا المصدرين الذين يخدمهما nginx:
     *   - /uploads/...  -> base_path('uploads/...')
     *   - /storage/...  -> storage_path('app/public/...')
     *
     * هذا متّسق مع تكوين nginx بعد توحيد المسارات.
     */
    public function imageExists()
    {
        $value = $this->image_url;
        if (!$value) {
            return false;
        }

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return true;
        }

        $path = ltrim($value, '/');

        if (str_starts_with($path, 'uploads/')) {
            return is_file(base_path($path));
        }

        if (str_starts_with($path, 'storage/')) {
            $rest = substr($path, strlen('storage/'));
            return is_file(storage_path('app/public/' . $rest));
        }

        if (str_starts_with($path, 'images/')) {
            return is_file(base_path('uploads/' . $path))
                || is_file(storage_path('app/public/' . $path));
        }

        return Storage::disk('public')->exists($path)
            || is_file(public_path($path));
    }

    /**
     * Set as primary image (unset others for this product)
     */
    public function setPrimary()
    {
        // First, unset any existing primary image for this product
        static::where('product_id', $this->product_id)
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);

        // Set this image as primary
        $this->update(['is_primary' => true]);
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
     * Reorder images for a product
     */
    public static function reorderForProduct($productId, array $imageIds)
    {
        foreach ($imageIds as $index => $imageId) {
            static::where('id', $imageId)
                ->where('product_id', $productId)
                ->update(['sort_order' => $index + 1]);
        }
    }

    /**
     * Get next sort order for a product
     */
    public static function getNextSortOrder($productId)
    {
        return static::where('product_id', $productId)->max('sort_order') + 1;
    }

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        // When creating a new image
        static::creating(function ($image) {
            // Set sort order if not provided
            if (!$image->sort_order) {
                $image->sort_order = static::getNextSortOrder($image->product_id);
            }

            // If this is set as primary and there's already a primary image
            if ($image->is_primary) {
                static::where('product_id', $image->product_id)
                    ->update(['is_primary' => false]);
            }
        });

        // When updating an image
        static::updating(function ($image) {
            // If setting as primary, unset others
            if ($image->isDirty('is_primary') && $image->is_primary) {
                static::where('product_id', $image->product_id)
                    ->where('id', '!=', $image->id)
                    ->update(['is_primary' => false]);
            }
        });

        // When deleting an image
        static::deleting(function ($image) {
            // If deleting a primary image, set another as primary
            if ($image->is_primary) {
                $nextPrimary = static::where('product_id', $image->product_id)
                    ->where('id', '!=', $image->id)
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->first();

                if ($nextPrimary) {
                    $nextPrimary->update(['is_primary' => true]);
                }
            }

            // حذف الملف الفعلي من المصادر المعروفة (uploads / storage/app/public).
            if (!filter_var($image->image_url, FILTER_VALIDATE_URL)) {
                $rel = ltrim((string) $image->image_url, '/');
                $candidates = [];
                if (str_starts_with($rel, 'uploads/')) {
                    $candidates[] = base_path($rel);
                } elseif (str_starts_with($rel, 'storage/')) {
                    $candidates[] = storage_path('app/public/' . substr($rel, strlen('storage/')));
                } else {
                    $candidates[] = base_path('uploads/' . $rel);
                    $candidates[] = storage_path('app/public/' . $rel);
                    $candidates[] = public_path($rel);
                }
                foreach ($candidates as $fp) {
                    if (is_file($fp)) {
                        @unlink($fp);
                    }
                }
            }
        });
    }
}