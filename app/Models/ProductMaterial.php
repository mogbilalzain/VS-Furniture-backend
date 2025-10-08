<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductMaterial extends Model
{
    protected $fillable = [
        'product_id',
        'material_id',
        'is_default',
        'sort_order',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'material_id' => 'integer',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the product that owns this relationship
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the material that owns this relationship
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    /**
     * Scope for default materials
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope for specific product
     */
    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope for specific material
     */
    public function scopeForMaterial($query, $materialId)
    {
        return $query->where('material_id', $materialId);
    }

    /**
     * Scope for ordered relationships
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Set this material as default for the product
     */
    public function setAsDefault()
    {
        // First, unset any existing default material for this product
        static::where('product_id', $this->product_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        // Set this material as default
        $this->update(['is_default' => true]);
    }

    /**
     * Get next sort order for new material on this product
     */
    public static function getNextSortOrder($productId)
    {
        return static::where('product_id', $productId)->max('sort_order') + 1;
    }

    /**
     * Check if a material is already assigned to a product
     */
    public static function isAssigned($productId, $materialId)
    {
        return static::where('product_id', $productId)
            ->where('material_id', $materialId)
            ->exists();
    }

    /**
     * Assign material to product
     */
    public static function assignMaterial($productId, $materialId, $isDefault = false, $sortOrder = null)
    {
        // Check if already assigned
        if (static::isAssigned($productId, $materialId)) {
            return null; // Already assigned
        }

        // Set sort order if not provided
        if ($sortOrder === null) {
            $sortOrder = static::getNextSortOrder($productId);
        }

        // Create the assignment
        $assignment = static::create([
            'product_id' => $productId,
            'material_id' => $materialId,
            'is_default' => $isDefault,
            'sort_order' => $sortOrder,
        ]);

        // If this is set as default, ensure no other material is default
        if ($isDefault) {
            $assignment->setAsDefault();
        }

        return $assignment;
    }

    /**
     * Reorder materials for a product
     */
    public static function reorderForProduct($productId, array $materialIds)
    {
        foreach ($materialIds as $index => $materialId) {
            static::where('product_id', $productId)
                ->where('material_id', $materialId)
                ->update(['sort_order' => $index + 1]);
        }
    }

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        // Set default sort order when creating
        static::creating(function ($productMaterial) {
            if (!isset($productMaterial->sort_order)) {
                $productMaterial->sort_order = static::getNextSortOrder($productMaterial->product_id);
            }

            // If this is the first material for the product, make it default
            $existingCount = static::where('product_id', $productMaterial->product_id)->count();
            if ($existingCount === 0) {
                $productMaterial->is_default = true;
            }
        });

        // Handle default setting
        static::updating(function ($productMaterial) {
            if ($productMaterial->isDirty('is_default') && $productMaterial->is_default) {
                // Unset other default materials for this product
                static::where('product_id', $productMaterial->product_id)
                    ->where('id', '!=', $productMaterial->id)
                    ->update(['is_default' => false]);
            }
        });
    }
}