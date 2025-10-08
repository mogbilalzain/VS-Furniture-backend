<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\ImageUrlHelper;

class SolutionImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'solution_id',
        'image_path',
        'alt_text',
        'sort_order'
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    /**
     * علاقة Many-to-One مع الحل
     */
    public function solution()
    {
        return $this->belongsTo(Solution::class);
    }

    /**
     * ترتيب الصور حسب sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * الحصول على الرابط الكامل للصورة
     */
    public function getFullUrlAttribute()
    {
        return ImageUrlHelper::getFullUrl($this->image_path);
    }

    /**
     * الحصول على الرابط المُحسَّن للصورة
     */
    public function getOptimizedUrlAttribute()
    {
        return ImageUrlHelper::getOptimizedUrl($this->image_path, 'medium');
    }

    /**
     * الحصول على الرابط المصغر للصورة
     */
    public function getThumbUrlAttribute()
    {
        return ImageUrlHelper::getOptimizedUrl($this->image_path, 'thumb');
    }

    /**
     * الحصول على معلومات الصورة
     */
    public function getImageInfoAttribute()
    {
        return ImageUrlHelper::getImageInfo($this->image_path);
    }
}