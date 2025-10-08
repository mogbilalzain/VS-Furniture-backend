<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaterialGroup extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the category that owns this group
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(MaterialCategory::class, 'category_id');
    }

    /**
     * Get the materials for this group
     */
    public function materials(): HasMany
    {
        return $this->hasMany(Material::class, 'group_id')->ordered();
    }

    /**
     * Get active materials for this group
     */
    public function activeMaterials(): HasMany
    {
        return $this->hasMany(Material::class, 'group_id')
            ->where('is_active', true)
            ->ordered();
    }

    /**
     * Scope for active groups
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for ordered groups
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Scope for specific category
     */
    public function scopeForCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Get next sort order for new group in this category
     */
    public static function getNextSortOrder($categoryId)
    {
        return static::where('category_id', $categoryId)->max('sort_order') + 1;
    }

    /**
     * Get materials count
     */
    public function getMaterialsCountAttribute()
    {
        return $this->materials()->count();
    }

    /**
     * Get active materials count
     */
    public function getActiveMaterialsCountAttribute()
    {
        return $this->activeMaterials()->count();
    }

    /**
     * Get category name
     */
    public function getCategoryNameAttribute()
    {
        return $this->category ? $this->category->name : null;
    }

    /**
     * Boot method to set default sort order
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($group) {
            if (!isset($group->sort_order)) {
                $group->sort_order = static::getNextSortOrder($group->category_id);
            }
        });
    }
}