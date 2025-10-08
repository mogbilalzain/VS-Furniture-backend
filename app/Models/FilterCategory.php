<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FilterCategory extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'display_name_ar',
        'sort_order',
        'input_type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the filter options for this category
     */
    public function filterOptions(): HasMany
    {
        return $this->hasMany(FilterOption::class);
    }

    /**
     * Get active filter options for this category
     */
    public function activeFilterOptions(): HasMany
    {
        return $this->hasMany(FilterOption::class)->where('is_active', true)->orderBy('sort_order');
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
        return $query->orderBy('sort_order');
    }
}