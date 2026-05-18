<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'name',
        'description',
        'slug',
        'image',
        'alt_text',
        'icon',
        'color',
        'status',
        'revenue',
        'orders_count',
        'is_active',
    ];

    protected $casts = [
        'revenue' => 'decimal:2',
        'orders_count' => 'integer',
    ];

    /**
     * Get products for this category
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get properties for this category
     */
    public function propertyGroups(): HasMany
    {
        return $this->hasMany(PropertyGroup::class)->orderBy('sort_order');
    }

    public function activePropertyGroups(): HasMany
    {
        return $this->hasMany(PropertyGroup::class)
            ->where('is_active', true)
            ->orderBy('sort_order');
    }

    public function properties(): HasMany
    {
        return $this->hasMany(CategoryProperty::class);
    }

    /**
     * Get active properties for this category
     */
    public function activeProperties(): HasMany
    {
        return $this->hasMany(CategoryProperty::class)
            ->where('is_active', true)
            ->orderBy('sort_order');
    }

    /**
     * Get all property values for this category
     */
    public function propertyValues()
    {
        return $this->hasManyThrough(
            PropertyValue::class,
            CategoryProperty::class,
            'category_id', // Foreign key on CategoryProperty table
            'category_property_id', // Foreign key on PropertyValue table
            'id', // Local key on Category table
            'id' // Local key on CategoryProperty table
        );
    }

    /**
     * Get products count
     */
    public function getProductsCountAttribute(): int
    {
        return $this->products()->count();
    }

    /**
     * Get properties count
     */
    public function getPropertiesCountAttribute(): int
    {
        return $this->properties()->count();
    }

    /**
     * Get active properties count
     */
    public function getActivePropertiesCountAttribute(): int
    {
        return $this->activeProperties()->count();
    }

    /**
     * Check if category has properties
     */
    public function hasProperties(): bool
    {
        return $this->properties()->exists();
    }

    /**
     * Get category with all its properties and values
     */
    public function withFullProperties()
    {
        return $this->load(['activeProperties.activePropertyValues']);
    }

    /**
     * Scope for categories with properties
     */
    public function scopeWithProperties($query)
    {
        return $query->whereHas('properties');
    }

    /**
     * Get category image URL with proper path.
     *
     * يطبّع أي صيغة قديمة (storage/, images/, categories/, اسم ملف فقط) إلى
     * `{APP_URL}/uploads/images/categories/...` عبر ImageHelper المُوحَّد.
     */
    public function getImageUrlAttribute()
    {
        return \App\Helpers\ImageHelper::buildFullUrl($this->image, 'categories');
    }

    /**
     * Get category properties as structured array
     */
    public function getStructuredPropertiesAttribute()
    {
        return $this->activeProperties->map(function($property) {
            return [
                'id' => $property->id,
                'name' => $property->name,
                'display_name' => $property->display_name,
                'description' => $property->description,
                'input_type' => $property->input_type,
                'is_required' => $property->is_required,
                'values' => $property->activePropertyValues->map(function($value) {
                    return [
                        'id' => $value->id,
                        'value' => $value->value,
                        'display_name' => $value->display_name,
                        'product_count' => $value->product_count,
                    ];
                }),
            ];
        });
    }
}
