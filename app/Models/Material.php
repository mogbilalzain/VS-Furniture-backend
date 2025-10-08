<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Material extends Model
{
    protected $fillable = [
        'group_id',
        'code',
        'name',
        'description',
        'color_hex',
        'image_url',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'group_id' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the group that owns this material
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(MaterialGroup::class, 'group_id');
    }

    /**
     * Get the category through the group
     */
    public function category()
    {
        return $this->hasOneThrough(
            MaterialCategory::class,
            MaterialGroup::class,
            'id',          // Foreign key on material_groups table
            'id',          // Foreign key on material_categories table
            'group_id',    // Local key on materials table
            'category_id'  // Local key on material_groups table
        );
    }

    /**
     * Get the products that use this material
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_materials')
            ->withPivot('is_default', 'sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    /**
     * Scope for active materials
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for ordered materials
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Scope for specific group
     */
    public function scopeForGroup($query, $groupId)
    {
        return $query->where('group_id', $groupId);
    }

    /**
     * Scope for specific category (through group)
     */
    public function scopeForCategory($query, $categoryId)
    {
        return $query->whereHas('group', function ($q) use ($categoryId) {
            $q->where('category_id', $categoryId);
        });
    }

    /**
     * Scope for materials with color hex
     */
    public function scopeWithColor($query)
    {
        return $query->whereNotNull('color_hex');
    }

    /**
     * Scope for materials with image
     */
    public function scopeWithImage($query)
    {
        return $query->whereNotNull('image_url');
    }

    /**
     * Get full image URL (handle both local and external URLs)
     */
    public function getFullImageUrlAttribute()
    {
        if (!$this->image_url) {
            return null;
        }

        // If it's already a full URL, return as is
        if (filter_var($this->image_url, FILTER_VALIDATE_URL)) {
            return $this->image_url;
        }

        // If it starts with '/', it's a local path
        if (str_starts_with($this->image_url, '/')) {
            return url($this->image_url);
        }

        // Otherwise, assume it's stored in storage
        return Storage::url($this->image_url);
    }

    /**
     * Check if material has visual representation (color or image)
     */
    public function getHasVisualAttribute()
    {
        return !empty($this->color_hex) || !empty($this->image_url);
    }

    /**
     * Get display type (color, image, or both)
     */
    public function getDisplayTypeAttribute()
    {
        $hasColor = !empty($this->color_hex);
        $hasImage = !empty($this->image_url);

        if ($hasColor && $hasImage) {
            return 'both';
        } elseif ($hasImage) {
            return 'image';
        } elseif ($hasColor) {
            return 'color';
        }

        return 'none';
    }

    /**
     * Check if image file exists
     */
    public function imageExists()
    {
        if (!$this->image_url) {
            return false;
        }

        // For external URLs, assume they exist
        if (filter_var($this->image_url, FILTER_VALIDATE_URL)) {
            return true;
        }

        // For local paths starting with '/'
        if (str_starts_with($this->image_url, '/')) {
            return file_exists(public_path($this->image_url));
        }

        // For storage paths
        return Storage::exists($this->image_url);
    }

    /**
     * Get next sort order for new material in this group
     */
    public static function getNextSortOrder($groupId)
    {
        return static::where('group_id', $groupId)->max('sort_order') + 1;
    }

    /**
     * Find material by code
     */
    public static function findByCode($code)
    {
        return static::where('code', $code)->first();
    }

    /**
     * Get group name
     */
    public function getGroupNameAttribute()
    {
        return $this->group ? $this->group->name : null;
    }

    /**
     * Get category name
     */
    public function getCategoryNameAttribute()
    {
        return $this->group && $this->group->category ? $this->group->category->name : null;
    }

    /**
     * Get products count
     */
    public function getProductsCountAttribute()
    {
        return $this->products()->count();
    }

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        // Set default sort order when creating
        static::creating(function ($material) {
            if (!isset($material->sort_order)) {
                $material->sort_order = static::getNextSortOrder($material->group_id);
            }
        });

        // Delete physical image file when deleting material
        static::deleting(function ($material) {
            if ($material->image_url && !filter_var($material->image_url, FILTER_VALIDATE_URL)) {
                if (str_starts_with($material->image_url, '/')) {
                    $filePath = public_path($material->image_url);
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                } else {
                    Storage::delete($material->image_url);
                }
            }
        });
    }
}