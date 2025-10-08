<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoryProperty extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'display_name',
        'display_name_ar',
        'description',
        'sort_order',
        'input_type',
        'is_required',
        'is_active',
        'is_filterable',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'sort_order' => 'integer',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'is_filterable' => 'boolean',
    ];

    /**
     * Get the category that owns this property
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the property values for this property
     */
    public function propertyValues(): HasMany
    {
        return $this->hasMany(PropertyValue::class, 'category_property_id');
    }

    /**
     * Get the active property values for this property
     */
    public function activePropertyValues(): HasMany
    {
        return $this->hasMany(PropertyValue::class, 'category_property_id')
            ->where('is_active', true)
            ->orderBy('sort_order');
    }

    /**
     * Scope for active properties
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for ordered properties
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Scope for properties by category
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope for required properties
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    /**
     * Get all products that have values for this property
     */
    public function products()
    {
        return $this->hasManyThrough(
            Product::class,
            ProductPropertyValue::class,
            'property_value_id', // Foreign key on ProductPropertyValue table
            'id', // Foreign key on Product table
            'id', // Local key on CategoryProperty table
            'product_id' // Local key on ProductPropertyValue table
        )->distinct();
    }

    /**
     * Update product counts for all values of this property
     */
    public function updateProductCounts()
    {
        $this->propertyValues()->each(function($value) {
            $value->updateProductCount();
        });
    }

    /**
     * Get input type display name
     */
    public function getInputTypeDisplayAttribute()
    {
        $types = [
            'checkbox' => 'اختيار متعدد',
            'radio' => 'اختيار واحد',
            'select' => 'قائمة منسدلة',
            'text' => 'نص',
            'number' => 'رقم',
        ];

        return $types[$this->input_type] ?? $this->input_type;
    }

    /**
     * Check if property has values
     */
    public function hasValues()
    {
        return $this->propertyValues()->count() > 0;
    }

    /**
     * Get total product count for this property
     */
    public function getTotalProductCountAttribute()
    {
        return $this->propertyValues()->sum('product_count');
    }
}