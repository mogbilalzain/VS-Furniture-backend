<?php

namespace App\Helpers;

class ImageUrlHelper
{
    /**
     * الحصول على الرابط الكامل للصورة
     *
     * @param string|null $imagePath
     * @return string|null
     */
    public static function getFullUrl($imagePath)
    {
        if (empty($imagePath)) {
            return self::getPlaceholderUrl();
        }

        // إذا كان الرابط كاملاً بالفعل (يحتوي على http/https)
        if (str_starts_with($imagePath, 'http')) {
            return $imagePath;
        }

        // إضافة domain للرابط
        $baseUrl = config('app.url');
        
        // التأكد من وجود slash في البداية
        if (!str_starts_with($imagePath, '/')) {
            $imagePath = '/' . $imagePath;
        }

        return $baseUrl . $imagePath;
    }

    /**
     * الحصول على رابط الصورة مع تحسين الحجم
     *
     * @param string|null $imagePath
     * @param string $size
     * @return string|null
     */
    public static function getOptimizedUrl($imagePath, $size = 'medium')
    {
        $sizes = [
            'thumb' => '150x150',
            'small' => '300x200',
            'medium' => '600x400',
            'large' => '1200x800',
            'original' => null
        ];

        $baseUrl = self::getFullUrl($imagePath);
        
        if ($baseUrl && isset($sizes[$size]) && $sizes[$size]) {
            return $baseUrl . '?size=' . $sizes[$size];
        }

        return $baseUrl;
    }

    /**
     * الحصول على رابط الصورة الافتراضية
     *
     * @return string
     */
    public static function getPlaceholderUrl()
    {
        return config('app.url') . '/images/placeholder-product.jpg';
    }

    /**
     * التحقق من صحة مسار الصورة
     *
     * @param string|null $imagePath
     * @return bool
     */
    public static function isValidImagePath($imagePath)
    {
        if (empty($imagePath)) {
            return false;
        }

        // قائمة الامتدادات المدعومة
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'];
        
        $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
        
        return in_array($extension, $allowedExtensions);
    }

    /**
     * تنظيف مسار الصورة
     *
     * @param string|null $imagePath
     * @return string|null
     */
    public static function sanitizePath($imagePath)
    {
        if (empty($imagePath)) {
            return null;
        }

        // إزالة الـ double slashes
        $imagePath = preg_replace('/\/+/', '/', $imagePath);
        
        // إزالة المسارات الخطيرة
        $imagePath = str_replace(['../', './'], '', $imagePath);
        
        return $imagePath;
    }

    /**
     * الحصول على معلومات الصورة
     *
     * @param string $imagePath
     * @return array|null
     */
    public static function getImageInfo($imagePath)
    {
        if (!self::isValidImagePath($imagePath)) {
            return null;
        }

        $fullPath = public_path($imagePath);
        
        if (!file_exists($fullPath)) {
            return null;
        }

        $size = getimagesize($fullPath);
        $fileSize = filesize($fullPath);
        
        return [
            'width' => $size[0] ?? null,
            'height' => $size[1] ?? null,
            'mime_type' => $size['mime'] ?? null,
            'file_size' => $fileSize,
            'file_size_human' => self::formatBytes($fileSize)
        ];
    }

    /**
     * تحويل حجم الملف إلى تنسيق قابل للقراءة
     *
     * @param int $size
     * @return string
     */
    private static function formatBytes($size)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        
        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }
        
        return round($size, 2) . ' ' . $units[$unitIndex];
    }
}
