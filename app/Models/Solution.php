<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\ImageUrlHelper;

class Solution extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'cover_image',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * علاقة One-to-Many مع صور الحل
     */
    public function images()
    {
        return $this->hasMany(SolutionImage::class)->orderBy('sort_order');
    }

    /**
     * علاقة Many-to-Many مع المنتجات
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'solution_products');
    }

    /**
     * فلترة الحلول النشطة فقط
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * فلترة الحلول مع المنتجات والصور
     */
    public function scopeWithRelations($query)
    {
        return $query->with(['images', 'products']);
    }

    /**
     * الحصول على عدد المنتجات المرتبطة
     */
    public function getProductsCountAttribute()
    {
        return $this->products()->count();
    }

    /**
     * الحصول على أول صورة إضافية (غير صورة الغلاف)
     */
    public function getFirstImageAttribute()
    {
        return $this->images()->first();
    }

    /**
     * الحصول على وصف مختصر
     */
    public function getShortDescriptionAttribute()
    {
        return strlen($this->description) > 150 
            ? substr($this->description, 0, 150) . '...' 
            : $this->description;
    }

    /**
     * الحصول على الرابط الكامل لصورة الغلاف
     */
    public function getCoverImageUrlAttribute()
    {
        return ImageUrlHelper::getFullUrl($this->cover_image);
    }

    /**
     * الحصول على الرابط المُحسَّن لصورة الغلاف
     */
    public function getCoverImageOptimizedAttribute()
    {
        return ImageUrlHelper::getOptimizedUrl($this->cover_image, 'medium');
    }

    /**
     * الحصول على الرابط المصغر لصورة الغلاف
     */
    public function getCoverImageThumbAttribute()
    {
        return ImageUrlHelper::getOptimizedUrl($this->cover_image, 'thumb');
    }
}