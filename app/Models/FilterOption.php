<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class FilterOption extends Model
{
    protected $fillable = [
        'filter_category_id',
        'value',
        'display_name',
        'display_name_ar',
        'sort_order',
        'product_count',
        'is_active',
    ];

    protected $casts = [
        'filter_category_id' => 'integer',
        'sort_order' => 'integer',
        'product_count' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the filter category for this option
     */
    public function filterCategory(): BelongsTo
    {
        return $this->belongsTo(FilterCategory::class);
    }

    /**
     * Get the products that have this filter option
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_filter_options')
            ->withPivot('custom_value')
            ->withTimestamps();
    }

    /**
     * Scope for active options
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for ordered options
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Update product count for this filter option
     */
    public function updateProductCount()
    {
        $this->update(['product_count' => $this->products()->count()]);
    }
}