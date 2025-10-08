<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'short_description',
        'specifications',
        'model',
        'category_id',
        'status',
        'is_featured',
        'sort_order',
        'views_count',
    ];

    protected $casts = [
        'specifications' => 'array',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
        'views_count' => 'integer',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
                
                // Ensure uniqueness
                $originalSlug = $product->slug;
                $counter = 1;
                while (static::where('slug', $product->slug)->exists()) {
                    $product->slug = $originalSlug . '-' . $counter++;
                }
            }
        });

        static::updating(function ($product) {
            if ($product->isDirty('name') && empty($product->slug)) {
                $product->slug = Str::slug($product->name);
                
                // Ensure uniqueness
                $originalSlug = $product->slug;
                $counter = 1;
                while (static::where('slug', $product->slug)->where('id', '!=', $product->id)->exists()) {
                    $product->slug = $originalSlug . '-' . $counter++;
                }
            }
        });
    }

    /**
     * Get category for this product
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get order items for this product
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the property values for this product
     */
    public function propertyValues(): BelongsToMany
    {
        return $this->belongsToMany(PropertyValue::class, 'product_property_values')
            ->withTimestamps();
    }

    /**
     * Get the files for this product
     */
    public function files(): HasMany
    {
        return $this->hasMany(ProductFile::class);
    }

    /**
     * Get active files for this product
     */
    public function activeFiles(): HasMany
    {
        return $this->hasMany(ProductFile::class)
            ->where('is_active', true)
            ->orderBy('sort_order');
    }

    /**
     * Get featured files for this product
     */
    public function featuredFiles(): HasMany
    {
        return $this->hasMany(ProductFile::class)
            ->where('is_featured', true)
            ->where('is_active', true)
            ->orderBy('sort_order');
    }

    /**
     * Scope for active products
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for featured products
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope for products ordered by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Get product properties grouped by property name
     */
    public function getGroupedPropertiesAttribute()
    {
        return ProductPropertyValue::getProductPropertiesGrouped($this->id);
    }

    /**
     * Attach property values to product
     */
    public function attachProperties(array $propertyValueIds)
    {
        return ProductPropertyValue::syncProductProperties($this->id, $propertyValueIds);
    }

    /**
     * Get property value for specific property
     */
    public function getPropertyValue($propertyName)
    {
        return $this->propertyValues()
            ->whereHas('categoryProperty', function($q) use ($propertyName) {
                $q->where('name', $propertyName);
            })
            ->first();
    }

    /**
     * Get all property values for specific property
     */
    public function getPropertyValues($propertyName)
    {
        return $this->propertyValues()
            ->whereHas('categoryProperty', function($q) use ($propertyName) {
                $q->where('name', $propertyName);
            })
            ->get();
    }

    /**
     * Check if product has specific property value
     */
    public function hasPropertyValue($propertyName, $value)
    {
        return $this->propertyValues()
            ->whereHas('categoryProperty', function($q) use ($propertyName) {
                $q->where('name', $propertyName);
            })
            ->where('value', $value)
            ->exists();
    }

    /**
     * Increment views count
     */
    public function incrementViews()
    {
        $this->increment('views_count');
    }

    /**
     * Get files count
     */
    public function getFilesCountAttribute()
    {
        return $this->files()->count();
    }

    /**
     * Get active files count
     */
    public function getActiveFilesCountAttribute()
    {
        return $this->activeFiles()->count();
    }

    /**
     * Scope to search products
     */
    public function scopeSearch($query, $search)
    {
        return         $query->where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('short_description', 'like', "%{$search}%")
              ->orWhere('model', 'like', "%{$search}%");
        });
    }

    /**
     * Scope to filter by properties
     */
    public function scopeWithProperties($query, array $filters)
    {
        foreach ($filters as $propertyName => $values) {
            if (empty($values)) continue;
            
            $query->whereHas('propertyValues', function($q) use ($propertyName, $values) {
                $q->whereHas('categoryProperty', function($categoryQuery) use ($propertyName) {
                    $categoryQuery->where('name', $propertyName);
                })
                ->whereIn('value', $values);
            });
        }
        
        return $query;
    }

    /**
     * علاقة Many-to-Many مع الشهادات
     */
    public function certifications(): BelongsToMany
    {
        return $this->belongsToMany(Certification::class, 'product_certifications');
    }

    /**
     * Get all images for this product
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->ordered();
    }

    /**
     * Get active images for this product
     */
    public function activeImages(): HasMany
    {
        return $this->hasMany(ProductImage::class)
            ->where('is_active', true)
            ->orderBy('sort_order');
    }

    /**
     * Get the primary image for this product
     */
    public function primaryImage()
    {
        return $this->hasOne(ProductImage::class)
            ->where('is_primary', true)
            ->where('is_active', true);
    }

    /**
     * Get featured images for this product
     */
    public function featuredImages(): HasMany
    {
        return $this->hasMany(ProductImage::class)
            ->where('is_featured', true)
            ->where('is_active', true)
            ->ordered();
    }

    /**
     * Get image URL from product_images (deprecated - use primary_image_url instead)
     */
    public function getImageUrlAttribute()
    {
        // This is now deprecated - always return primary image from product_images
        return $this->primary_image_url;
    }

    /**
     * Get primary image URL from product_images table
     */
    public function getPrimaryImageUrlAttribute()
    {
        $primaryImage = $this->primaryImage()->first();
        
        if ($primaryImage) {
            return $primaryImage->full_image_url;
        }

        // No primary image found - return null
        return null;
    }

    /**
     * Get all image URLs as array
     */
    public function getImageUrlsAttribute()
    {
        $urls = $this->activeImages->pluck('full_image_url')->toArray();
        
        // If no images and legacy image exists, include it
        if (empty($urls) && $this->image) {
            $urls[] = $this->image;
        }
        
        return $urls;
    }

    /**
     * Get all materials for this product
     */
    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(Material::class, 'product_materials')
            ->withPivot('is_default', 'sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    /**
     * Get default material for this product
     */
    public function defaultMaterial()
    {
        return $this->belongsToMany(Material::class, 'product_materials')
            ->withPivot('is_default', 'sort_order')
            ->wherePivot('is_default', true)
            ->first();
    }

    /**
     * Get materials grouped by category
     */
    public function getMaterialsByCategoryAttribute()
    {
        return $this->materials()
            ->with(['group.category'])
            ->get()
            ->groupBy(function ($material) {
                return $material->group->category->name ?? 'Uncategorized';
            });
    }

    /**
     * Check if product has materials
     */
    public function getHasMaterialsAttribute()
    {
        return $this->materials()->count() > 0;
    }

    /**
     * Get materials count
     */
    public function getMaterialsCountAttribute()
    {
        return $this->materials()->count();
    }

    /**
     * علاقة Many-to-Many مع الحلول
     */
    public function solutions(): BelongsToMany
    {
        return $this->belongsToMany(Solution::class, 'solution_products');
    }

    /**
     * Get solutions count
     */
    public function getSolutionsCountAttribute()
    {
        return $this->solutions()->count();
    }
}
