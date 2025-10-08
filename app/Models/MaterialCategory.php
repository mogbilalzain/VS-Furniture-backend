<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class MaterialCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate slug when creating
        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });

        // Auto-update slug when updating name
        static::updating(function ($category) {
            if ($category->isDirty('name') && empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    /**
     * Get the material groups for this category
     */
    public function materialGroups(): HasMany
    {
        return $this->hasMany(MaterialGroup::class, 'category_id')->ordered();
    }

    /**
     * Get active material groups for this category
     */
    public function activeMaterialGroups(): HasMany
    {
        return $this->hasMany(MaterialGroup::class, 'category_id')
            ->where('is_active', true)
            ->ordered();
    }

    /**
     * Get all materials through groups
     */
    public function materials()
    {
        return $this->hasManyThrough(
            Material::class,
            MaterialGroup::class,
            'category_id', // Foreign key on material_groups table
            'group_id',    // Foreign key on materials table
            'id',          // Local key on material_categories table
            'id'           // Local key on material_groups table
        )->ordered();
    }

    /**
     * Get active materials through groups
     */
    public function activeMaterials()
    {
        return $this->hasManyThrough(
            Material::class,
            MaterialGroup::class,
            'category_id',
            'group_id',
            'id',
            'id'
        )->where('materials.is_active', true)
         ->where('material_groups.is_active', true)
         ->ordered();
    }

    /**
     * Scope for active categories
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for ordered categories
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Get next sort order for new category
     */
    public static function getNextSortOrder()
    {
        return static::max('sort_order') + 1;
    }

    /**
     * Find category by slug
     */
    public static function findBySlug($slug)
    {
        return static::where('slug', $slug)->first();
    }

    /**
     * Get category URL-friendly slug
     */
    public function getRouteKeyName()
    {
        return 'slug';
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
}