<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPropertyValue extends Model
{
    protected $fillable = [
        'product_id',
        'property_value_id',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'property_value_id' => 'integer',
    ];

    /**
     * Get the product for this property value
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the property value
     */
    public function propertyValue(): BelongsTo
    {
        return $this->belongsTo(PropertyValue::class);
    }

    /**
     * Get the category property through property value
     */
    public function categoryProperty()
    {
        return $this->hasOneThrough(
            CategoryProperty::class,
            PropertyValue::class,
            'id', // Foreign key on PropertyValue table
            'id', // Foreign key on CategoryProperty table
            'property_value_id', // Local key on ProductPropertyValue table
            'category_property_id' // Local key on PropertyValue table
        );
    }

    /**
     * Get the category through property value and category property
     */
    public function category()
    {
        return $this->hasOneThrough(
            Category::class,
            CategoryProperty::class,
            'category_id', // Foreign key on CategoryProperty table
            'id', // Foreign key on Category table
            'property_value_id', // Local key on ProductPropertyValue table
            'id' // Local key on CategoryProperty table
        );
    }

    /**
     * Scope for specific product
     */
    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope for specific property value
     */
    public function scopeForPropertyValue($query, $propertyValueId)
    {
        return $query->where('property_value_id', $propertyValueId);
    }

    /**
     * Scope for specific category
     */
    public function scopeForCategory($query, $categoryId)
    {
        return $query->whereHas('propertyValue.categoryProperty', function($q) use ($categoryId) {
            $q->where('category_id', $categoryId);
        });
    }

    /**
     * Scope for specific property
     */
    public function scopeForProperty($query, $propertyId)
    {
        return $query->whereHas('propertyValue', function($q) use ($propertyId) {
            $q->where('category_property_id', $propertyId);
        });
    }

    /**
     * Get property name
     */
    public function getPropertyNameAttribute()
    {
        return $this->propertyValue->categoryProperty->name ?? '';
    }

    /**
     * Get property display name
     */
    public function getPropertyDisplayNameAttribute()
    {
        return $this->propertyValue->categoryProperty->display_name ?? '';
    }

    /**
     * Get value display name
     */
    public function getValueDisplayNameAttribute()
    {
        return $this->propertyValue->display_name ?? '';
    }

    /**
     * Get the full property info as array
     */
    public function getPropertyInfoAttribute()
    {
        return [
            'property_id' => $this->propertyValue->category_property_id ?? null,
            'property_name' => $this->property_name,
            'property_display_name' => $this->property_display_name,
            'value_id' => $this->property_value_id,
            'value' => $this->propertyValue->value ?? '',
            'value_display_name' => $this->value_display_name,
        ];
    }

    /**
     * Static method to sync product properties
     */
    public static function syncProductProperties($productId, array $propertyValueIds)
    {
        // Delete existing relations
        static::where('product_id', $productId)->delete();
        
        // Create new relations
        $relations = [];
        foreach ($propertyValueIds as $propertyValueId) {
            $relations[] = [
                'product_id' => $productId,
                'property_value_id' => $propertyValueId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        if (!empty($relations)) {
            static::insert($relations);
        }
        
        // Update product counts for affected property values
        PropertyValue::whereIn('id', $propertyValueIds)->each(function($value) {
            $value->updateProductCount();
        });
        
        return count($relations);
    }

    /**
     * Static method to get product properties grouped by property
     */
    public static function getProductPropertiesGrouped($productId)
    {
        return static::with(['propertyValue.categoryProperty'])
            ->where('product_id', $productId)
            ->get()
            ->groupBy(function($item) {
                return $item->propertyValue->categoryProperty->name ?? 'unknown';
            })
            ->map(function($group) {
                return $group->map(function($item) {
                    return $item->property_info;
                });
            });
    }
}