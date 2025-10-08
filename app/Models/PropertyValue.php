<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PropertyValue extends Model
{
    protected $fillable = [
        'category_property_id',
        'value',
        'display_name',
        'display_name_ar',
        'sort_order',
        'product_count',
        'is_active',
    ];

    protected $casts = [
        'category_property_id' => 'integer',
        'sort_order' => 'integer',
        'product_count' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the category property for this value
     */
    public function categoryProperty(): BelongsTo
    {
        return $this->belongsTo(CategoryProperty::class, 'category_property_id');
    }

    /**
     * Get the products that have this property value
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_property_values')
            ->withTimestamps();
    }

    /**
     * Get the category through the property
     */
    public function category()
    {
        return $this->categoryProperty->category();
    }

    /**
     * Scope for active values
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for ordered values
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Scope for values by property
     */
    public function scopeByProperty($query, $propertyId)
    {
        return $query->where('category_property_id', $propertyId);
    }

    /**
     * Scope for values by category
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->whereHas('categoryProperty', function($q) use ($categoryId) {
            $q->where('category_id', $categoryId);
        });
    }

    /**
     * Update product count for this value
     */
    public function updateProductCount()
    {
        $count = $this->products()->count();
        $this->update(['product_count' => $count]);
        return $count;
    }

    /**
     * Get display name with product count
     */
    public function getDisplayNameWithCountAttribute()
    {
        return $this->display_name . ' (' . $this->product_count . ')';
    }

    /**
     * Check if value is used by any products
     */
    public function isUsed()
    {
        return $this->product_count > 0;
    }

    /**
     * Get property name for this value
     */
    public function getPropertyNameAttribute()
    {
        return $this->categoryProperty->name ?? '';
    }

    /**
     * Get property display name for this value
     */
    public function getPropertyDisplayNameAttribute()
    {
        return $this->categoryProperty->display_name ?? '';
    }

    /**
     * Get category name for this value
     */
    public function getCategoryNameAttribute()
    {
        return $this->categoryProperty->category->name ?? '';
    }

    /**
     * Scope to search by value or display name
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('value', 'like', "%{$search}%")
              ->orWhere('display_name', 'like', "%{$search}%")
              ->orWhere('display_name_ar', 'like', "%{$search}%");
        });
    }

    /**
     * Get all products in the same category with this value
     */
    public function getCategoryProducts()
    {
        return Product::whereHas('category', function($q) {
            $q->where('id', $this->categoryProperty->category_id);
        });
    }
}