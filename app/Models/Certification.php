<?php

namespace App\Models;

use App\Helpers\ImageHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Certification extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description', 
        'image_url',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * تطبيع image_url إلى رابط مطلق وموحَّد عبر ImageHelper::buildFullUrl.
     *
     * يحوّل أي صيغة قديمة (مثل /images/certifications/X.png) إلى
     * {APP_URL}/uploads/images/certifications/X.png. الروابط المطلقة (http/https)
     * تمر دون تعديل.
     */
    public function getImageUrlAttribute($value)
    {
        if (!$value) {
            return null;
        }

        return ImageHelper::buildFullUrl($value, 'certifications');
    }

    /**
     * علاقة Many-to-Many مع المنتجات
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_certifications');
    }

    /**
     * فلترة الشهادات النشطة فقط
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
